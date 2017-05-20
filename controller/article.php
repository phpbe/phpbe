<?php

namespace controller;

use \system\be;
use \system\db;
use \system\request;
use \system\response;
use \system\exception\custom_exception;

class article extends \system\controller
{

    public function home()
    {
        $model_article = be::get_model('article');

        // 最新带图文章
        $latest_thumbnail_articles = $model_article->get_articles([
            'thumbnail' => 1,
            'order_by' => 'create_time',
            'order_by_dir' => 'DESC',
            'limit' => 6
        ]);

        $active_users = $model_article->get_active_users();

        // 本月热点
        $month_hottest_articles = $model_article->get_articles([
            'order_by' => 'hits',
            'order_by_dir' => 'DESC',
            'from_time' => time() - 86400 * 30,
            'limit' => 6
        ]);

        // 推荐文章
        $top_articles = $model_article->get_articles([
            'top' => 1,
            'order_by' => 'top',
            'order_by_dir' => 'DESC',
            'limit' => 6
        ]);

        $top_categories = array();
        $categories = $model_article->get_categories();
        foreach ($categories as $category) {
            if ($category->parent_id > 0) continue;
            $top_categories[] = $category;

            $category->articles = $model_article->get_articles([
                'category_id' => $category->id,
                'order_by' => 'create_time',
                'order_by_dir' => 'DESC',
                'limit' => 6
            ]);
        }

        $config_system = be::get_config('system');
        response::set_title($config_system->home_title);
        response::set_meta_keywords($config_system->home_meta_keywords);
        response::set_meta_description($config_system->home_meta_description);
        response::set('latest_thumbnail_articles', $latest_thumbnail_articles);
        response::set('active_users', $active_users);
        response::set('month_hottest_articles', $month_hottest_articles);
        response::set('top_articles', $top_articles);
        response::set('categories', $top_categories);
        response::display();
    }

    public function articles()
    {
        $config_article = be::get_config('article');

        $category_id = request::get('category_id', 0, 'int');
        response::set('category_id', $category_id);

        $row_article_category = be::get_row('article_category');
        $row_article_category->cache($config_article->cache_expire);
        $row_article_category->load($category_id);

        if ($row_article_category->id == 0) response::end('文章分类不存在！');

        response::set_title($row_article_category->name);
        response::set('category', $row_article_category);

        if ($row_article_category->parent_id > 0) {
            $parent_category = null;
            $tmp_category = $row_article_category;
            while ($tmp_category->parent_id > 0) {
                $parent_id = $tmp_category->parent_id;
                $tmp_category = be::get_row('article_category');
                $tmp_category->load($parent_id);
            }
            $parent_category = $tmp_category;
            response::set('parent_category', $parent_category);

            $north_menu = be::get_menu('north');
            $north_menu_tree = $north_menu->get_menu_tree();
            if (count($north_menu_tree)) {
                $menu_exist = false;
                foreach ($north_menu_tree as $menu) {
                    if (
                        isset($menu->params['controller']) && $menu->params['controller'] == 'article' &&
                        isset($menu->params['task']) && $menu->params['task'] == 'listing' &&
                        isset($menu->params['category_id']) && $menu->params['category_id'] == $parent_category->id
                    ) {
                        response::set('menu_id', $menu->id);
                        break;
                    }
                }
            }
        } else {
            response::set('parent_category', $row_article_category);
        }

        $model_article = be::get_model('article');

        $option = array('category_id' => $category_id);

        $limit = 10;
        $pagination = be::get_ui('pagination');
        $pagination->set_limit($limit);
        $pagination->set_total($model_article->get_article_count($option));
        $pagination->set_page(request::get('page', 1, 'int'));
        $pagination->set_url('controller=article&task=articles&category_id=' . $category_id);
        response::set('pagination', $pagination);

        $option['offset'] = $pagination->get_offset();
        $option['limit'] = $limit;
        $option['order_by_string'] = '`top` DESC, `rank` DESC, `create_time` DESC';

        $articles = $model_article->get_articles($option);
        response::set('articles', $articles);

        // 热门文章
        $hottest_articles = $model_article->get_articles([
            'category_id' => $category_id,
            'order_by' => 'hits',
            'order_by_dir' => 'DESC',
            'limit' => 10
        ]);
        response::set('hottest_articles', $hottest_articles);

        // 推荐文章
        $top_articles = $model_article->get_articles(array('category_id' => $category_id, 'top' => 1, 'order_by' => 'top', 'order_by_dir' => 'DESC', 'limit' => 10));
        response::set('top_articles', $top_articles);

        response::display('article.articles');
    }


    public function detail()
    {
        $config_article = be::get_config('article');

        $article_id = request::get('article_id', 0, 'int');
        if ($article_id == 0) response::end('参数(article_id)缺失！');

        $row_article = be::get_row('article');
        $row_article->cache($config_article->cache_expire);
        $row_article->load($article_id);
        $row_article->increment('hit', 1); // 点击量加 1

        $model_article = be::get_model('article');

        $similar_articles = $model_article->get_similar_articles($row_article, 10);

        // 热门文章
        $hottest_articles = $model_article->get_articles([
            'category_id' => $row_article->category_id,
            'order_by' => 'hits',
            'order_by_dir' => 'DESC',
            'limit' => 10
        ]);

        // 推荐文章
        $top_articles = $model_article->get_articles([
            'category_id' => $row_article->category_id,
            'top' => 1,
            'order_by' => 'top',
            'order_by_dir' => 'DESC',
            'limit' => 10
        ]);

        $comments = $model_article->get_comments([
            'article_id' => $article_id
        ]);

        response::set_title($row_article->title);
        response::set_meta_keywords($row_article->meta_keywords);
        response::set_meta_description($row_article->meta_description);

        $north_menu = be::get_menu('north');
        $north_menu_tree = $north_menu->get_menu_tree();
        if (count($north_menu_tree)) {
            $menu_exist = false;
            foreach ($north_menu_tree as $menu) {
                if (
                    isset($menu->params['controller']) && $menu->params['controller'] == 'article' &&
                    isset($menu->params['task']) && $menu->params['task'] == 'detail' &&
                    isset($menu->params['article_id']) && $menu->params['article_id'] == $article_id
                ) {
                    response::set('menu_id', $menu->id);
                    if ($menu->home == 1) response::set('home', 1);
                    $menu_exist = true;
                    break;
                }
            }

            if (!$menu_exist) {
                foreach ($north_menu_tree as $menu) {
                    if (
                        isset($menu->params['controller']) && $menu->params['controller'] == 'article' &&
                        isset($menu->params['task']) && $menu->params['task'] == 'listing' &&
                        isset($menu->params['category_id']) && $menu->params['category_id'] == $row_article->category_id
                    ) {
                        response::set('menu_id', $menu->id);
                        $menu_exist = true;
                        break;
                    }
                }
            }
        }

        response::set('article', $row_article);
        response::set('similar_articles', $similar_articles);
        response::set('hottest_articles', $hottest_articles);
        response::set('top_articles', $top_articles);
        response::set('comments', $comments);
        response::display();
    }


    // 喜欢
    public function ajax_like()
    {
        $my = be::get_user();
        if ($my->id == 0) {
            response::error('请先登陆！');
        }

        $article_id = request::get('article_id', 0, 'int');
        if ($article_id == 0) {
            response::error('参数(article_id)缺失！');
        }

        try {
            db::begin_transaction();

            $row_article = be::get_row('article');
            $row_article->load($article_id);
            if ($row_article->id == 0 || $row_article->block == 1) {
                throw new custom_exception('文章不存在！');
            }

            $row_article_vote_log = be::get_row('article_vote_log');
            $row_article_vote_log->load(['article_id' => $article_id, 'user_id' => $my->id]);
            if ($row_article_vote_log->id > 0) {
                throw new custom_exception('您已经表过态啦！');
            }
            $row_article_vote_log->article_id = $article_id;
            $row_article_vote_log->user_id = $my->id;
            $row_article_vote_log->save();

            $model_article = be::get_model('article');
            $model_article->like($article_id);

            db::commit();
        } catch (custom_exception $e) {
            db::rollback();
            response::error($e->getMessage());
        }

        response::success('提交成功！');
    }

    // 不喜欢
    public function ajax_dislike()
    {
        $my = be::get_user();
        if ($my->id == 0) {
            response::error('请先登陆！');
        }

        $article_id = request::get('article_id', 0, 'int');
        if ($article_id == 0) {
            response::error('参数(article_id)缺失！');
        }

        try {
            db::begin_transaction();

            $row_article = be::get_row('article');
            $row_article->load($article_id);
            if ($row_article->id == 0 || $row_article->block == 1) {
                throw new custom_exception('文章不存在！');
            }

            $row_article_vote_log = be::get_row('article_vote_log');
            $row_article_vote_log->load(['article_id' => $article_id, 'user_id' => $my->id]);
            if ($row_article_vote_log->id > 0) {
                throw new custom_exception('您已经表过态啦！');
            }
            $row_article_vote_log->article_id = $article_id;
            $row_article_vote_log->user_id = $my->id;
            $row_article_vote_log->save();

            $model_article = be::get_model('article');
            $model_article->dislike($article_id);

            db::commit();
        } catch (custom_exception $e) {
            db::rollback();
            response::error($e->getMessage());
        }

        response::success('提交成功！');
    }


    public function ajax_comment()
    {
        $my = be::get_user();
        if ($my->id == 0) {
            response::error('请先登陆！');
        }

        $article_id = request::post('article_id', 0, 'int');
        if ($article_id == 0) {
            response::error('参数(article_id)缺失！');
        }

        $row_article = be::get_row('article');
        $row_article->load($article_id);
        if ($row_article->id == 0 || $row_article->block == 1) {
            response::error('文章不存在！');
        }

        $body = request::post('body', '');
        $body = trim($body);
        $body_length = strlen($body);
        if ($body_length == 0) {
            response::error('请输入评论内容！');
        }

        if ($body_length > 2000) {
            response::error('评论内容过长！');
        }

        $row_article_comment = be::get_row('article_comment');
        $row_article_comment->article_id = $article_id;
        $row_article_comment->user_id = $my->id;
        $row_article_comment->user_name = $my->name;
        $row_article_comment->body = $body;
        $row_article_comment->ip = $_SERVER['REMOTE_ADDR'];
        $row_article_comment->create_time = time();

        $config_article = be::get_config('article');
        $row_article_comment->block = ($config_article->comment_public == 1 ? 0 : 1);

        $row_article_comment->save();

        response::success('提交成功！');
    }

    // 顶
    public function ajax_comment_like()
    {
        $my = be::get_user();
        if ($my->id == 0) {
            response::error('请先登陆！');
        }

        $comment_id = request::get('comment_id', 0, 'int');
        if ($comment_id == 0) {
            response::error('参数(comment_id)缺失！');
        }

        try {
            db::begin_transaction();

            $row_article_comment = be::get_row('article_comment');
            $row_article_comment->load($comment_id);
            if ($row_article_comment->id == 0 || $row_article_comment->block == 1) {
                throw new custom_exception('评论不存在！');
            }

            $row_article_vote_log = be::get_row('article_vote_log');
            $row_article_vote_log->load(['comment_id' => $comment_id, 'user_id' => $my->id]);
            if ($row_article_vote_log->id > 0) {
                throw new custom_exception('您已经表过态啦！');
            }
            $row_article_vote_log->comment_id = $comment_id;
            $row_article_vote_log->user_id = $my->id;
            $row_article_vote_log->save();

            $model_article = be::get_model('article');
            $model_article->comment_like($comment_id);

            db::commit();
        } catch (custom_exception $e) {
            db::rollback();
            response::error($e->getMessage());
        }

        response::success('提交成功！');
    }

    // 踩
    public function ajax_comment_dislike()
    {
        $my = be::get_user();
        if ($my->id == 0) {
            response::error('请先登陆！');
        }

        $comment_id = request::get('comment_id', 0, 'int');
        if ($comment_id == 0) {
            response::error('参数(comment_id)缺失！');
        }

        try {
            db::begin_transaction();

            $row_article_comment = be::get_row('article_comment');
            $row_article_comment->load($comment_id);
            if ($row_article_comment->id == 0 || $row_article_comment->block == 1) {
                throw new custom_exception('评论不存在！');
            }

            $row_article_vote_log = be::get_row('article_vote_log');
            $row_article_vote_log->load(['comment_id' => $comment_id, 'user_id' => $my->id]);
            if ($row_article_vote_log->id > 0) {
                throw new custom_exception('您已经表过态啦！');
            }
            $row_article_vote_log->comment_id = $comment_id;
            $row_article_vote_log->user_id = $my->id;
            $row_article_vote_log->save();

            $model_article = be::get_model('article');
            $model_article->comment_dislike($comment_id);

            db::commit();
        } catch (custom_exception $e) {
            db::rollback();
            response::error($e->getMessage());
        }

        response::success('提交成功！');
    }

    public function user()
    {
        $user_id = request::get('user_id', 0, 'int');
        if ($user_id == 0) response::end('参数(user_id)缺失！');

        $user = be::get_user($user_id);
        if ($user->block == 1) response::end('该用户账号已被停用！');

        $model_article = be::get_model('article');

        $option = ['user_id' => $user_id, 'order_by' => 'create_time', 'order_by_dir' => 'DESC', 'limit' => 30];
        $articles = $model_article->get_articles($option);
        $article_count = $model_article->get_article_count($option);

        $option = ['user_id' => $user_id, 'order_by' => 'create_time', 'order_by_dir' => 'DESC', 'limit' => 30];
        $comments = $model_article->get_comments($option);
        foreach ($comments as $comment) {
            $row_article = be::get_row('article');
            $row_article->load($comment->article_id);
            $comment->article = $row_article;
        }
        $comment_count = $model_article->get_comment_count($option);

        response::set_title($user->name . ' 的动态');
        response::set_meta_keywords($user->name . ' 的动态');
        response::set_meta_description($user->name . ' 的动态');
        response::set('user', $user);
        response::set('articles', $articles);
        response::set('article_count', $article_count);
        response::set('comments', $comments);
        response::set('comment_count', $comment_count);
        response::display();
    }

}

?>