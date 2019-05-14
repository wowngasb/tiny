<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/25 0025
 * Time: 15:05
 */

namespace Tiny\Plugin\develop\controller;

use Tiny\Application;
use Tiny\Plugin\ApiHelper;
use Tiny\Plugin\develop\DevelopController;


/**
 * 项目部署控制器，进行一些脚本
 * Class Deploy
 * @package app\controllers
 */
class deploy extends DevelopController
{
    public function beforeAction(array $params)
    {
        $params = parent::beforeAction($params);

        if (!self::authDevelopKey($this->getRequest())) {  //认证 不通过
            Application::redirect($this->getResponse(), Application::url($this->getRequest(), ['', 'index', 'index']));
        }

        return $params;
    }

    public function phpInfo()
    {
        phpinfo();
    }

    public function initSuperAdmin()
    {
        $admin_id = 0;  // TODO 判断当前是否存在管理员
        if ($admin_id > 0) {
            return $this->getResponse()->appendBody('pass');
        }
        $username = $this->_post('username', '');
        $password = $this->_post('password', '');
        if (empty($password) || empty($username)) {
            $html_str = <<<EOT
<form action="" method="POST">
    登陆账号：<input type="text" value="" placeholder="登录名" name="username">
    登录密码：<input type="password" placeholder="密码" name="password">
    <button type="submit">创建超级管理员</button>
</form>
EOT;
            return $this->getResponse()->appendBody($html_str);
        }
        $admin_id = 0;  // TODO 创建管理员帐号
        if ($admin_id > 0) {
            $html_str = "init {$username} at {$admin_id} ok";
            return $this->getResponse()->appendBody($html_str);
        }
        return $this->getResponse()->appendBody('create admin error');
    }

    public function syncEnvConfig()
    {
        $html_str = 'Sync Config Surceased';  // TODO 获取基本配置
        $this->getResponse()->appendBody($html_str);
    }


    /**
     * 编译根目录api下所有 API 类 生成 js  放到 static/apiMod 下面
     */
    public function buildApiModJs()
    {
        $dev_debug = $this->_get('dev_debug', 0) == 1;
        $js_list = self::_buildApiModJs($dev_debug);
        $html_str = join($js_list, '<br />');
        $this->getResponse()->appendBody($html_str);
    }

    public static function _buildApiModJs($dev_debug = 0)
    {
        $appname = Application::appname();
        $ret = [];

        $api_path = Application::path([$appname, 'api']);
        $api_list = ApiHelper::getApiFileList($api_path);
        foreach ($api_list as $key => $val) {
            $class = str_replace('.php', '', $val['name']);
            $out_file = $class . '.js';
            $class_name = "\\{$appname}\\api\\{$class}";
            $method_list = ApiHelper::getApiMethodList($class_name);
            $js_str = ApiHelper::model2js($class, $method_list, $dev_debug);
            $out_path = Application::path(['public', 'static', 'api']);
            if (!is_dir($out_path)) {
                mkdir($out_path, 0777, true);
            }
            file_put_contents($out_path . $out_file, $js_str, LOCK_EX);
            $js_len = strlen($js_str);
            $ret[] = "build:{$out_file} ({$js_len})";
        }
        return $ret;
    }

    public function actionGetModelJs()
    {
        $dev_debug = $this->_get('dev_debug', Application::dev());
        $dev_debug = !empty($dev_debug);
        $method_list = [];
        $cls = $this->_get('cls', '');
        if (!empty($cls)) {
            $class_name = '\\api\\' . $cls;
            $method_list = ApiHelper::getApiMethodList($class_name);
        }
        $html_str = ApiHelper::model2js($cls, $method_list, $dev_debug);
        $this->getResponse()->appendBody($html_str);
    }

    public function cleanCache()
    {
        $mCache = self::_getCacheInstance();
        if (!empty($mCache)) {
            $mCache->clear();
        }
        $html_str = "clear cache";
        $this->getResponse()->appendBody($html_str);
    }

}
