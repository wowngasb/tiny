<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/8/14
 * Time: 12:33
 */

namespace Tiny\Plugin;


use Tiny\Application;
use Tiny\Controller\ControllerSimple;
use Tiny\Exception\AppStartUpError;
use Tiny\Func;

class DevAuthControllerSimple extends ControllerSimple
{

    private static $_SVR_DEVELOP_KEY = 'develop_key';
    private static $_SVR_DEVELOP_EXPIRY = 86400; //24小时

    protected function sendFile($file_path)
    {
        $response = $this->getResponse();
        $content_type = Func::mime_content_type($file_path);
        if (!is_file($file_path)) {
            $response->addHeader("Content-Type:{$content_type}", true, 404);
        } elseif (!is_readable($file_path)) {
            $response->addHeader("Content-Type:{$content_type}", true, 403);
        } else {
            $response->addHeader("Content-Type:{$content_type}", true, 200);
            $response->appendBody(file_get_contents($file_path));
        }
    }

    protected function _showLoginBox($develop_key)
    {
        $this->_delCookieDevelopKey();
        $err_msg = empty($develop_key) ? 'Input develop key.' : 'Auth failed.';
        $html_str = <<<EOT
<form action="" method="POST">
    Auth：<input type="text" value="{$develop_key}" placeholder="develop_key" name="develop_key">
    <button type="submit">Login</button>
</form>
<span>{$err_msg}</span>
EOT;
        $this->getResponse()->appendBody($html_str);
    }


    public function authDevelopKey()
    {
        $env_develop_key = Application::app()->getEnv('ENV_DEVELOP_KEY', '');
        if (empty($env_develop_key)) {
            throw new AppStartUpError('must set ENV_DEVELOP_KEY in config');
        }
        $develop_key = $this->_getCookieDevelopKey();
        $test = Func::str_cmp($env_develop_key, $develop_key);
        $test && $this->_setCookieDevelopKey($develop_key);
        return $test;
    }

    protected function _getCookieDevelopKey()
    {
        $name = self::$_SVR_DEVELOP_KEY;
        $crypt_key = Application::app()->getEnv('ENV_CRYPT_KEY');
        $auth_str = $this->_cookie($name, '');
        $develop_key = Func::decode($auth_str, $crypt_key);
        return $develop_key;
    }

    protected function _checkRequestDevelopKeyToken()
    {
        $dev_token = $this->_request('dev_token', '');
        if (!empty($dev_token)) {
            $crypt_key = Application::app()->getEnv('ENV_CRYPT_KEY');
            $develop_key = Func::decode($dev_token, $crypt_key);
            $develop_key && $this->_setCookieDevelopKey($develop_key);
        }
    }

    protected function _setCookieDevelopKey($develop_key)
    {
        $name = self::$_SVR_DEVELOP_KEY;
        $crypt_key = Application::app()->getEnv('ENV_CRYPT_KEY');
        $value = Func::encode($develop_key, $crypt_key, self::$_SVR_DEVELOP_EXPIRY);
        $this->getRequest()->setcookie($name, $value, time() + self::$_SVR_DEVELOP_EXPIRY, '/');
        $this->getRequest()->set_cookie($name, $value);
    }

    protected function _delCookieDevelopKey()
    {
        $name = self::$_SVR_DEVELOP_KEY;
        $value = '';
        $this->getRequest()->setcookie($name, $value, time() + self::$_SVR_DEVELOP_EXPIRY, '/');
    }


}