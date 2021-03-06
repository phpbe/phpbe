<?php
namespace App\System\Service;

use Phpbe\System\Be;

class Theme extends \Phpbe\System\Service
{
    private $beApi = 'http://api.phpbe.com/';

    private $theme = null;

    public function getThemes()
    {
        if ($this->theme === null) {
            $this->theme = array();

            $dir = dir(Be::getRuntime()->getPathRoot() . '/theme');
            while (($file = $dir->read()) !== false) {
                if ($file != '.' && $file != '..' && is_dir(Be::getRuntime()->getPathRoot() . '/theme/' .  $file)) {
                    if (file_exists(Be::getRuntime()->getPathRoot() . '/theme/' .  $file . '/config.php')) {
                        include(Be::getRuntime()->getPathRoot() . '/theme/' .  $file . '/config.php');
                        $className = 'configTheme_' . $file;
                        if (class_exists($className)) {
                            $this->theme[$file] = new $className();
                        }
                    }
                }

            }
            $dir->close();
        }
        return $this->theme;
    }

    public function getThemeCount()
    {
        return count($this->getThemes());
    }

    public function setDefaultTheme($theme)
    {
        $configSystem = Be::getConfig('System.System');
        $configSystem->theme = $theme;

        Be::getService('system')->updateConfig($configSystem, Be::getRuntime()->getPathRoot() . '/Config/system.php');

        return true;
    }


    public function getRemoteThemes($option = array())
    {
        $libHttp = Be::getLib('Http');
        $Response = $libHttp->post($this->beApi . 'theme/', $option);

        $theme = json_decode($Response);
        return $theme;
    }

    public function getRemoteTheme($themeId)
    {
        $libHttp = Be::getLib('Http');
        $Response = $libHttp->get($this->beApi . 'theme/' . $themeId);

        $theme = json_decode($Response);
        return $theme;
    }


    // 安装应用文件
    public function installTheme($theme)
    {
        $dir = Be::getRuntime()->getPathRoot() . '/theme/' .  $theme->name;
        if (file_exists($dir)) {
            throw new \Exception('安装主题所需要的文件夹（/theme/' . $theme->name . '/）已被占用，请删除后重新安装！');
        }

        $libHttp = Be::getLib('Http');
        $Response = $libHttp->get($this->beApi . 'themeDownload/' . $theme->id . '/');

        $zip = PATH_ADMIN . '/tmp/theme_' . $theme->name . '.zip';
        file_put_contents($zip, $Response);

        $libZip = Be::getLib('zip');
        $libZip->open($zip);
        if (!$libZip->extractTo($dir)) {
            throw new \Exception($libZip->getError());
        }

        // 删除临时文件
        unlink($zip);
    }

    // 删除主题
    public function uninstallTheme($theme)
    {
        $configSystem = Be::getConfig('System.System');

        if ($configSystem->theme == $theme) {
            throw new \Exception('正在使用的默认主题不能删除');
        }

        $themePath = Be::getRuntime()->getPathRoot() . '/theme/' .  $theme;

        $libFso = Be::getLib('fso');
        $libFso->rmDir($themePath);
    }

}
