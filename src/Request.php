<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/24 0024
 * Time: 14:28
 */

namespace Tiny;

use Tiny\Exception\AppStartUpError;

/**
 * Class Request
 * @package Tiny
 */
final class Request
{
    protected $_request_uri = '/';  // 当前请求的Request URI
    protected $_method = 'GET';  // 当前请求的Method, 对于命令行来说, Method为"CLI"
    protected $_language = ''; // 当前请求的希望接受的语言, 对于Http请求来说, 这个值来自分析请求头Accept-Language. 对于不能鉴别的情况, 这个值为空.
    protected $_routed = false; // 表示当前请求是否已经完成路由 完成后 不可修改路由和参数信息
    protected $_http_referer = '';
    protected $_this_url = '';
    protected $_csrf_token = '';

    protected $_current_route = '';  // 当前使用的 路由名称 在注册路由时给出的
    protected $_route_info = [];  // 当前 路由信息 [$controller, $action, $module]
    protected $_params = [];  // 匹配到的参数 用于调用 action

    private $_session_started = false;
    private $_session_start = false;

    private $_request_timestamp = null;

    private static $_instance = null;

    /**
     * @param $uri
     * @return Request
     */
    public function cloneAndHookUri($uri)
    {
        $tmp = clone $this;
        $tmp->_request_uri = $uri;
        $tmp->setUnRouted();
        return $tmp;
    }

    public function __construct()
    {
        $this->_request_timestamp = microtime(true);
        $this->_request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $this->_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';
        $this->_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
        $this->_http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $this->_this_url = SYSTEM_HOST . substr($this->_request_uri, 1);
        $this->_csrf_token = self::_request('CSRF', '');
    }

    /**
     * @return Request
     */
    public static function instance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    /**
     * @return bool
     */
    public function isSessionStarted()
    {
        return $this->_session_started;
    }

    /**
     * @return bool
     */
    public function isSessionStart()
    {
        return $this->_session_start;
    }

    /**
     * 控制是否启用 session
     * @param bool $session_start
     * @return $this
     */
    public function setSessionStart($session_start)
    {
        $this->_session_start = $session_start;
        if ($this->isSessionStart() && !$this->isSessionStarted()) {
            session_start();
            $this->_session_started = true;
        }
        return $this;
    }

    public function getSessionId()
    {
        return $this->_session_started ? session_id() : '';
    }


    public function get_request_timestamp()
    {
        return $this->_request_timestamp;
    }

    public function getCsrfToken()
    {
        return $this->_csrf_token;
    }

    public function getThisUrl()
    {
        return $this->_this_url;
    }

    public function getHttpReferer()
    {
        return $this->_http_referer;
    }

    /**
     * @return array
     */
    public function getRouteInfo()
    {
        return $this->_route_info;
    }

    /**
     * @return array
     */
    public function strRouteInfo()
    {
        $arr = $this->getRouteInfo();
        return "{$arr[0]}/{$arr[1]}/{$arr[2]}";
    }

    /**
     * @param array $routeInfo
     * @return $this
     * @throws AppStartUpError
     */
    public function setRouteInfo(array $routeInfo)
    {
        if ($this->_routed) {
            throw new AppStartUpError('request has been routed');
        }
        if (count($routeInfo) !== 3 || empty($routeInfo[0]) || empty($routeInfo[1]) || empty($routeInfo[2])) {
            throw new AppStartUpError('not like [module, Controller, Action] routeInfo:' . json_encode($routeInfo));
        }
        $this->_route_info = [Func::trimlower($routeInfo[0]), Func::trimlower($routeInfo[1]), Func::trimlower($routeInfo[2])];
        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * 设置本次请求入口方法的参数 只有第一次设置有效
     * @param array $params
     * @return $this
     * @throws AppStartUpError
     */
    public function setParams(array $params)
    {
        if (is_null($this->_params)) {
            $this->_params = $params;
        }
        return $this;
    }

    /**
     * @param string $current_route
     * @return $this
     * @throws AppStartUpError
     */
    public function setCurrentRoute($current_route)
    {
        if ($this->_routed) {
            throw new AppStartUpError('request has been routed');
        }
        $this->_current_route = $current_route;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentRoute()
    {
        return $this->_current_route;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * @return bool
     */
    public function isRouted()
    {
        return $this->_routed;
    }

    /**
     * @return $this
     */
    public function setRouted()
    {
        $this->_routed = true;
        return $this;
    }

    /**
     * @return $this
     * @throws AppStartUpError
     */
    public function setUnRouted()
    {
        $this->_routed = false;
        $this->_current_route = '';
        $this->_params = [];
        $this->_route_info = [];
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestPath()
    {
        $tmp = explode('?', $this->_request_uri);
        $path = !empty($tmp[0]) ? $tmp[0] : '/';
        while (strpos($path, '//') !== false) {
            $path = str_replace('//', '/', $path);
        }
        if (substr($path, -1, 1) != '/') {
            $path .= '/';
        }
        return Func::trimlower($path);
    }

    /**
     * 根据 路由信息 和 参数 按照路由规则生成 url
     * @param Request $request
     * @param array $routerArr 格式为 [$module, $controller, $action] 使用当前相同 设置为空即可
     * @param array $params
     * @return string
     * @throws AppStartUpError
     */
    public static function urlTo(Request $request, array $routerArr = [], array $params = [])
    {
        $route = $request->getCurrentRoute();
        $routerArr = Func::mergeNotEmpty($request->getRouteInfo(), $routerArr);
        return Application::app()->getRoute($route)->url($routerArr, $params);
    }

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function _get($name = null, $default = '')
    {
        return is_null($name) ? $_GET : (isset($_GET[$name]) ? $_GET[$name] : $default);
    }

    public function set_get($name, $data)
    {
        return $_GET[$name] = $data;
    }

    /**
     * @param $name
     * @param string $default
     * @return string
     */
    public function _post($name = null, $default = '')
    {
        return is_null($name) ? $_POST : (isset($_POST[$name]) ? $_POST[$name] : $default);
    }

    public function set_post($name, $data)
    {
        return $_POST[$name] = $data;
    }

    /**
     * @param $name
     * @param string $default
     * @return string
     */
    public function _env($name = null, $default = '')
    {
        return is_null($name) ? $_ENV : (isset($_ENV[$name]) ? $_ENV[$name] : $default);
    }

    public function set_env($name, $data)
    {
        return $_ENV[$name] = $data;
    }

    /**
     * @param $name
     * @param string $default
     * @return string
     */
    public function _server($name = null, $default = '')
    {
        return is_null($name) ? $_SERVER : (isset($_SERVER[$name]) ? $_SERVER[$name] : $default);
    }

    public function set_server($name, $data)
    {
        return $_SERVER[$name] = $data;
    }

    /**
     * @param $name
     * @param string $default
     * @return string
     */
    public function _cookie($name = null, $default = '')
    {
        return is_null($name) ? $_COOKIE : (isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default);
    }

    public function set_cookie($name, $data)
    {
        return $_COOKIE[$name] = $data;
    }

    public function setcookie($name, $value, $expire = 0, $path = "", $domain = "", $secure = false, $httponly = false)
    {
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * @param $name
     * @param string $default
     * @return string
     */
    public function _files($name = null, $default = '')
    {
        return is_null($name) ? $_FILES : (isset($_FILES[$name]) ? $_FILES[$name] : $default);
    }

    public function set_files($name, $data)
    {
        return $_FILES[$name] = $data;
    }

    /**
     * @param $name
     * @param string $default
     * @return array | string
     */
    public function _request($name = null, $default = '')
    {
        return is_null($name) ? $_REQUEST : (isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default);
    }

    public function set_request($name, $data)
    {
        return $_REQUEST[$name] = $data;
    }

    /**
     * @param $name
     * @param string $default
     * @return string
     */
    public function _session($name = null, $default = '')
    {
        return is_null($name) ? (!empty($_SESSION) ? $_SESSION : []) : (isset($_SESSION[$name]) ? $_SESSION[$name] : $default);
    }

    public function set_session($name, $data)
    {
        return $_SESSION[$name] = $data;
    }

    /**
     * @return string
     */
    public function getRequestUri()
    {
        return $this->_request_uri;
    }
}