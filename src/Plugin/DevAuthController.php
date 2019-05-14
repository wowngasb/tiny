<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/8/14
 * Time: 12:33
 */

namespace Tiny\Plugin;


use Tiny\Application;
use Tiny\Controller\SimpleController;
use Tiny\Exception\AppStartUpError;
use Tiny\Interfaces\RequestInterface;
use Tiny\Util;

class DevAuthController extends SimpleController
{

    protected $template_dir = '';

    private static $_SVR_DEVELOP_KEY = 'develop_key';
    private static $_SVR_DEVELOP_EXPIRY = 86400; //24小时
    private static $_SVR_DEVELOP_EXPIRY_DEV = 86400 * 100; // 2400 小时

    protected function sendFile($file_path)
    {
        $response = $this->getResponse();
        $content_type = Util::mime_content_type($file_path);
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
        self::_delDevelopKey($this->getRequest());
        $back = $this->_request('back', '');
        $back = !empty($back) ? $back : '/develop';

        $err_msg = empty($develop_key) ? 'Input develop key.' : 'Auth failed.';
        $style = empty($develop_key) ? '' : 'color: red;';

        $html_str = <<<EOT
<form action="" method="POST">
    Auth：<input type="text" value="{$develop_key}" placeholder="develop_key" name="develop_key">
    <input type="hidden" name="back" value="{$back}">
    <button type="submit">Login</button>
</form>
<span style="{$style}">{$err_msg}</span>
EOT;
        $this->getResponse()->appendBody($html_str);
    }


    protected function _checkRequestDevelopKeyToken()
    {
        $dev_token = $this->_request('dev_token', '');
        $dev_token = !empty($dev_token) ? $dev_token : $this->_request('devtoken', '');

        if (!empty($dev_token)) {
            $crypt_key = Application::config('CRYPT_KEY');
            $develop_key = Util::decode($dev_token, $crypt_key);
            $develop_key && self::_setDevelopKey($this->getRequest(), $develop_key);
        }
    }

    final public static function authDevelopKey(RequestInterface $request)
    {
        $test = self::_checkDevelopKey($request);
        $test && self::_setDevelopKey($request, Application::config('ENV_DEVELOP_KEY'));
        return $test;
    }

    final public static function _checkDevelopKey(RequestInterface $request)
    {
        $env_develop_key = Application::config('ENV_DEVELOP_KEY');
        if (empty($env_develop_key)) {
            throw new AppStartUpError('must set ENV_DEVELOP_KEY in config');
        }
        $develop_key = self::_getDevelopKey($request);
        return Util::str_cmp($env_develop_key, $develop_key);
    }

    final public static function _getDevelopKey(RequestInterface $request)
    {
        $name = self::$_SVR_DEVELOP_KEY;
        $crypt_key = Application::config('CRYPT_KEY');
        $auth_str = $request->_cookie($name, '');
        $develop_key = Util::decode($auth_str, $crypt_key);
        $develop_key = !empty($develop_key) ? $develop_key : $request->_request('dev_token', '');
        $develop_key = !empty($develop_key) ? $develop_key : $request->_request('devtoken', '');
        return $develop_key;
    }

    final public static function _setDevelopKey(RequestInterface $request, $develop_key)
    {
        $name = self::$_SVR_DEVELOP_KEY;
        $expiry = Application::dev() ? self::$_SVR_DEVELOP_EXPIRY_DEV : self::$_SVR_DEVELOP_EXPIRY;
        $crypt_key = Application::config('CRYPT_KEY');
        $value = Util::encode($develop_key, $crypt_key, $expiry);
        $request->setcookie($name, $value, time() + $expiry, '/');
        $request->set_cookie($name, $value);
    }

    final public function _delDevelopKey(RequestInterface $request)
    {
        $name = self::$_SVR_DEVELOP_KEY;
        $value = '';
        $expiry = Application::dev() ? self::$_SVR_DEVELOP_EXPIRY_DEV : self::$_SVR_DEVELOP_EXPIRY;
        $request->setcookie($name, $value, time() + $expiry, '/');
    }


}