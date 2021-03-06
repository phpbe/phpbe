<?php
namespace theme\huxiu;


class config
{
  /*
  在BE系统官网上存放的主题 ID, 用于识别用户安装。
  不需要通过BE系统官网管理时可以直接设为 0
  */
  public $id = 0;
  
  public $name = '仿虎嗅网'; // 主题名称
  public $description = '仿虎嗅网主题'; // 主题描述
  
  public $author = 'Lou Barnes';  // 作者
  public $authorEmail = 'lou@loubarnes.com'; // 作者邮箱
  public $authorWebsite = 'http://www.1024i.com'; // 作者网站
  
  /*
  缩略图文件，保存在主题目录下
  */
  public $thumbnailL = 'l.jpg';  // 缩略图大图 800 x 800 px
  public $thumbnailM = 'm.jpg';  // 缩略图中图 400 x 400 px
  public $thumbnailS = 's.jpg';  // 缩略图小图 200 x 200 px

    public $colors = array('#2D2D2D');		// 主题 主色
}
