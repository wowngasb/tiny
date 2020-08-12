<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/24 0024
 * Time: 14:28
 */

namespace Tiny;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Tiny\Exception\AppStartUpError;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;
use Tiny\Plugin\UploadedFile;

/**
 * Class StdRequest
 * 默认 StdRequest 请求参数来源 使用默认 php 的 超全局变量
 * @package Tiny
 */
class StdRequest extends SymfonyRequest implements RequestInterface
{

    protected $_routed = false; // 表示当前请求是否已经完成路由 完成后 不可修改路由和参数信息
    protected $_current_route = '';  // 当前使用的 路由名称 在注册路由时给出的
    protected $_route_info = [];  // 当前 路由信息 [$controller, $action, $module]
    protected $_params = [];  // 匹配到的参数 用于调用 action

    protected $_session_started = false;
    protected $_request_timestamp = null;

    protected $_cache_map = [];

    /** @var ResponseInterface */
    protected $_response = null;

    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        $this->_request_timestamp = microtime(true);
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    /**
     * @param bool $enableHttpMethodParameterOverride
     * @return RequestInterface
     */
    public static function createFromGlobals($enableHttpMethodParameterOverride = false)
    {
        if ($enableHttpMethodParameterOverride) {
            static::enableHttpMethodParameterOverride();
        }
        /** @var RequestInterface $request */
        $request = parent::createFromGlobals();
        return $request;
    }

    /**
     * 绑定
     * @param ResponseInterface $response
     * @throws AppStartUpError
     */
    public function bindingResponse(ResponseInterface $response)
    {
        if (!is_null($this->_response)) {
            throw new AppStartUpError('bindingResponse only run once');
        }
        $this->_response = $response;
    }

    /**
     * @return ResponseInterface
     */
    public function getBindingResponse()
    {
        return $this->_response;
    }

    ###############################################################
    ############  私有属性 getter setter ################
    ###############################################################

    /**
     * @return bool
     */
    public function isSessionStarted()
    {
        return $this->_session_started;
    }

    /**
     * @return float
     */
    public function getRequestTimestamp()
    {
        if (defined('DEBUG_START_MICRO_TIMESTAMP')) {
            return DEBUG_START_MICRO_TIMESTAMP;
        }
        return $this->_request_timestamp;
    }

    /**
     * @return string
     */
    public function getHttpReferer()
    {
        return $this->_server('HTTP_REFERER', '');
    }

    /**
     * @return array
     */
    public function getRouteInfo()
    {
        return $this->_route_info;
    }

    /**
     * @param array $routeInfo
     * @return StdRequest
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
        $this->_route_info = [trim($routeInfo[0]), trim($routeInfo[1]), trim($routeInfo[2])];
        return $this;
    }

    /**
     * @return string
     */
    public function getRouteInfoAsUri()
    {
        $arr = $this->getRouteInfo();
        return !empty($arr) ? "{$arr[0]}/{$arr[1]}/{$arr[2]}" : '/';
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * 设置本次请求入口方法的参数
     * @param array $params
     * @return StdRequest
     * @throws AppStartUpError
     */
    public function setParams(array $params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * @param string $current_route
     * @return StdRequest
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
     * @return bool
     */
    public function isRouted()
    {
        return $this->_routed;
    }

    /**
     * @param bool $is_routed
     * @return StdRequest
     */
    public function setRouted($is_routed = true)
    {
        $this->_routed = $is_routed;
        return $this;
    }

    ###############################################################
    ############  启动及运行相关函数 ################
    ###############################################################

    /**
     * @return int
     */
    public function usedMilliSecond()
    {
        return round(microtime(true) - $this->getRequestTimestamp(), 3) * 1000;
    }

    /**
     * @param null $tag
     * @return null|string
     */
    public function debugTag($tag = null)
    {
        if (!empty($tag)) {
            $t = $this->usedMilliSecond();
            if ($t > 0 && $t < 1000) {
                $tag = "{$tag} @{$t}ms";
            } elseif ($t >= 1000) {
                $ts = $t / 1000;
                $tag = "{$tag} @{$ts}s";
            }
        }
        return $tag;
    }

    /**
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return bool
     */
    public function setcookie($name, $value, $expire = 0, $path = "", $domain = "", $secure = false, $httponly = false)
    {
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * 设置 session 保存 句柄
     * @param \SessionHandlerInterface $sessionhandler
     * @param bool $register_shutdown
     * @return RequestInterface
     */
    public function session_set_save_handler(\SessionHandlerInterface $sessionhandler, $register_shutdown = true)
    {
        session_set_save_handler($sessionhandler, $register_shutdown);
        return $this;
    }

    function session_name($name = null)
    {
        return !empty($name) ? session_name($name) : session_name();
    }

    /**
     * 启用 session
     * @return StdRequest
     */
    public function session_start()
    {
        if (!$this->_session_started) {
            session_start();
            //error_log(date('Y-m-d H:i:s') . " TEST TRY session_status:" . session_status() . ", session_id:" . session_id());
            $this->_session_started = true;
        }
        return $this;
    }

    public function session_status()
    {
        return session_status();
    }

    /**
     * @param null $id
     * @return null|string
     */
    public function session_id($id = null)
    {
        return !empty($id) ? session_id($id) : trim(session_id());
    }

    /**
     * @return StdRequest
     * @throws AppStartUpError
     */
    public function reset_route()
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
    public function fixRequestPath()
    {
        $path = $this->getRequestUri();
        $idx = strpos($path, '#');
        if ($idx > 0) {
            $path = substr($path, 0, $idx);
        }
        $idx = strpos($path, '?');
        if ($idx > 0) {
            $path = substr($path, 0, $idx);
        }
        while (strpos($path, '//') !== false) {
            $path = str_replace('//', '/', $path);
        }

        return $path;
    }

    ###############################################################
    ############  超全局变量 ################
    ###############################################################

    ##################  $_GET_ ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string
     */
    public function _get($name, $default = '', $setBack = false, $popKey = false)
    {
        $val = isset($_GET[$name]) ? $_GET[$name] : $default;
        if ($setBack) {
            $this->set_get($name, $val);
        }
        if ($popKey) {
            $this->del_get($name);
        }
        return $val;
    }

    /**
     * @return array
     */
    public function all_get()
    {
        return !empty($_GET) ? $_GET : [];
    }

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_get($name, $data)
    {
        $_GET[$name] = $data;
    }

    /**
     * @param string $name
     */
    public function del_get($name)
    {
        unset($_GET[$name]);
    }

    ##################  $this->__post ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string
     */
    public function _post($name, $default = '', $setBack = false, $popKey = false)
    {
        $val = isset($_POST[$name]) ? $_POST[$name] : $default;
        if ($setBack) {
            $this->set_post($name, $val);
        }
        if ($popKey) {
            $this->del_post($name);
        }
        return $val;
    }

    /**
     * @return array
     */
    public function all_post()
    {
        return !empty($_POST) ? $_POST : [];
    }

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_post($name, $data)
    {
        $_POST[$name] = $data;
    }

    /**
     * @param string $name
     */
    public function del_post($name)
    {
        unset($_POST[$name]);
    }

    ##################  $_ENV_ ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string
     */
    public function _env($name, $default = '', $setBack = false, $popKey = false)
    {
        $val = isset($_ENV[$name]) ? $_ENV[$name] : $default;
        if ($setBack) {
            $this->set_env($name, $val);
        }
        if ($popKey) {
            $this->del_env($name);
        }
        return $val;
    }

    /**
     * @return array
     */
    public function all_env()
    {
        return !empty($_ENV) ? $_ENV : [];
    }

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_env($name, $data)
    {
        $_ENV[$name] = $data;
    }

    /**
     * @param string $name
     */
    public function del_env($name)
    {
        unset($_ENV[$name]);
    }

    ##################  $_SERVER_ ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string
     */
    public function _server($name, $default = '', $setBack = false, $popKey = false)
    {
        $val = isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
        if ($setBack) {
            $this->set_server($name, $val);
        }
        if ($popKey) {
            $this->del_server($name);
        }
        return $val;
    }

    /**
     * @return array
     */
    public function all_server()
    {
        return !empty($_SERVER) ? $_SERVER : [];
    }

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_server($name, $data)
    {
        $_SERVER[$name] = $data;
    }

    /**
     * @param string $name
     */
    public function del_server($name)
    {
        unset($_SERVER[$name]);
    }

    ##################  $_COOKIE_ ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string
     */
    public function _cookie($name, $default = '', $setBack = false, $popKey = false)
    {
        $val = isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
        if ($setBack) {
            $this->set_cookie($name, $val);
        }
        if ($popKey) {
            $this->del_cookie($name);
        }
        return $val;
    }

    /**
     * @return array
     */
    public function all_cookie()
    {
        return !empty($_COOKIE) ? $_COOKIE : [];
    }

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_cookie($name, $data)
    {
        $_COOKIE[$name] = $data;
    }

    /**
     * @param string $name
     */
    public function del_cookie($name)
    {
        unset($_COOKIE[$name]);
    }

    ##################  $_FILES_ ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string
     */
    public function _files($name, $default = '', $setBack = false, $popKey = false)
    {
        $val = isset($_FILES[$name]) ? $_FILES[$name] : $default;
        if ($setBack) {
            $this->set_files($name, $val);
        }
        if ($popKey) {
            $this->del_files($name);
        }
        return $val;
    }

    /**
     * @return array
     */
    public function all_files()
    {
        return !empty($_FILES) ? $_FILES : [];
    }

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_files($name, $data)
    {
        $_FILES[$name] = $data;
    }

    /**
     * @param string $name
     */
    public function del_files($name)
    {
        unset($_FILES[$name]);
    }

    ##################  $_REQUEST_ ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string
     */
    public function _request($name, $default = '', $setBack = false, $popKey = false)
    {
        $val = isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
        if ($setBack) {
            $this->set_request($name, $val);
        }
        if ($popKey) {
            $this->del_request($name);
        }
        return $val;
    }

    /**
     * @return array
     */
    public function all_request()
    {
        return !empty($_REQUEST) ? $_REQUEST : [];
    }

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_request($name, $data)
    {
        $_REQUEST[$name] = $data;
    }

    /**
     * @param string $name
     */
    public function del_request($name)
    {
        unset($_REQUEST[$name]);
    }

    ##################  $_SESSION_ ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string|array
     */
    public function _session($name, $default = '', $setBack = false, $popKey = false)
    {
        $val = isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
        if ($setBack) {
            $this->set_session($name, $val);
        }
        if ($popKey) {
            $this->del_session($name);
        }
        return $val;
    }

    /**
     * @return array
     */
    public function all_session()
    {
        return !empty($_SESSION) ? $_SESSION : [];
    }

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_session($name, $data)
    {
        $_SESSION[$name] = $data;
    }

    /**
     * @param string $name
     */
    public function del_session($name)
    {
        unset($_SESSION[$name]);
    }

    ###############################################################
    ######################  文件处理相关 ##########################
    ###############################################################

    /**
     * All of the converted files for the request.
     *
     * @var array
     */
    protected $convertedFiles = [];

    /**
     * Get an array of all of the files on the request.
     *
     * @return array
     */
    public function allFiles()
    {
        if (!empty($this->convertedFiles)) {
            return $this->convertedFiles;
        }
        $files = $this->files->all();
        $this->convertedFiles = self::convertUploadedFiles($files);
        return $this->convertedFiles;
    }

    /**
     * Convert the given array of Symfony UploadedFiles to custom Laravel UploadedFiles.
     *
     * @param array $files
     * @return array
     */
    protected static function convertUploadedFiles(array $files)
    {
        return array_map(function ($file) {
            /** @var mixed $file */
            if (is_null($file) || (is_array($file) && empty(array_filter($file)))) {
                return $file;
            }

            return is_array($file)
                ? self::convertUploadedFiles($file)
                : UploadedFile::createFromBase($file);
        }, $files);
    }

    /**
     * Retrieve a file from the request.
     *
     * @param string $key
     * @param mixed $default
     * @return UploadedFile|array|null
     */
    public function file($key = null, $default = null)
    {
        return is_null($key) ? $this->allFiles() : Util::v($this->allFiles(), $key, $default);
    }

    /**
     * Determine if the uploaded data contains a file.
     *
     * @param string $key
     * @return bool
     */
    public function hasFile($key)
    {
        if (!is_array($files = $this->file($key))) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($this->isValidFile($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check that the given file is a valid file instance.
     *
     * @param mixed $file
     * @return bool
     */
    protected function isValidFile($file)
    {
        return $file instanceof \SplFileInfo && $file->getPath() != '';
    }

    ###############################################################
    ############  测试相关 可以伪造 请求的各种参数 ################
    ###############################################################

    /**
     * @param string $method
     * @param string $uri
     * @param array $args
     * @return StdRequest
     */
    public function copyHttpArgs($method = null, $uri = null, array $args = [])
    {
        $tmp = clone $this;
        if (!is_null($method)) {
            $tmp->method = $method;
        }
        if (!is_null($uri)) {
            $tmp->requestUri = $uri;
        }
        $tmp->resetHttpArgs();
        $tmp->hookHttpArgs($args);
        $tmp->reset_route();
        return $tmp;
    }

    public function hookHttpArgs(array $args = [])
    {
        if (empty($args)) {
            return;
        }

        if (isset($args['GET'])) {
            $_GET = $args['GET'];
        }
        if (isset($args['POST'])) {
            $_POST = $args['POST'];
        }

        $_REQUEST = array_merge($_GET, $_POST);  //  默认按照 GET POST 的顺序覆盖  不包含 COOKIE 的值

        if (isset($args['SERVER'])) {
            $_SERVER = $args['SERVER'];
        }
        if (isset($args['ENV'])) {
            $_ENV = $args['ENV'];
        }
        if (isset($args['COOKIE'])) {
            $_COOKIE = $args['COOKIE'];
        }
        if (isset($args['FILES'])) {
            $_FILES = $args['FILES'];
        }
        if (isset($args['SESSION'])) {
            $_SESSION = $args['SESSION'];
        }

        if (isset($args['php://input'])) {
            $this->_cache_map['raw_post_data'] = $args['php://input'];
        }

        if (isset($args['path'])) {
            $this->_cache_map['path'] = $args['path'];
        }

        if (isset($args['ajax'])) {
            $this->_cache_map['ajax'] = $args['ajax'];
        }

        if (isset($args['host'])) {
            $this->_cache_map['host'] = $args['host'];
        }

        if (isset($args['schema'])) {
            $this->_cache_map['schema'] = $args['schema'];
        }

        if (isset($args['client_ip'])) {
            $this->_cache_map['client_ip'] = $args['client_ip'];
        }

        if (isset($args['request_header'])) {
            $this->_cache_map['request_header'] = $args['request_header'];
        }

        if (isset($args['agent_browser'])) {
            $this->_cache_map['agent_browser'] = $args['agent_browser'];
        }

        if (isset($args['is_mobile'])) {
            $this->_cache_map['is_mobile'] = $args['is_mobile'];
        }
    }

    public function resetHttpArgs()
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_COOKIE = [];
        $_FILES = [];
        $_SESSION = [];
        $this->_cache_map = [];
    }

    /**
     * 获取 完整 url
     * @return string
     */
    public function full()
    {
        $schema = $this->schema();
        $host = $this->host();
        return "{$schema}://{$host}" . $this->getRequestUri();
    }

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function _header($name, $default = '')
    {
        $all_header = $this->request_header();
        $name = Util::trimlower($name);
        $val = isset($all_header[$name]) ? $all_header[$name] : $default;
        return $val;
    }

    ##################  HTTP INFO ##################

    /**
     * 判断是否 https
     * @return bool
     */
    public function is_https()
    {
        $https = $this->_server('HTTPS', '');
        if (!empty($https) && strtolower($https) !== 'off') {
            return true;
        }

        $http_x_forwarded_proto = $this->_server('HTTP_X_FORWARDED_PROTO', '');
        if (!empty($http_x_forwarded_proto) && strtolower($http_x_forwarded_proto) === 'https') {
            return true;
        }

        $http_front_end_https = $this->_server('HTTP_FRONT_END_HTTPS', '');
        if (!empty($http_front_end_https) && strtolower($http_front_end_https) !== 'off') {
            return true;
        }

        return false;
    }

    public function path()
    {
        if (isset($this->_cache_map['path'])) {
            return $this->_cache_map['path'];
        }

        $path = substr($this->fixRequestPath(), 1);
        $this->_cache_map['path'] = $path;
        return $this->_cache_map['path'];
    }

    public function ajax()
    {
        if (isset($this->_cache_map['ajax'])) {
            return $this->_cache_map['ajax'];
        }

        $header = $this->request_header();
        $key = 'X-Requested-With';
        $val = Util::v($header, strtolower($key), '');
        $ajax = Util::stri_cmp($val, 'XMLHttpRequest');
        $this->_cache_map['ajax'] = $ajax;
        return $this->_cache_map['ajax'];
    }

    public function host()
    {
        if (isset($this->_cache_map['host'])) {
            return $this->_cache_map['host'];
        }

        $host = $this->_server('HTTP_HOST', 'localhost');
        $this->_cache_map['host'] = $host;
        return $this->_cache_map['host'];
    }

    public function schema()
    {
        if (isset($this->_cache_map['schema'])) {
            return $this->_cache_map['schema'];
        }

        $schema = $this->is_https() ? 'https' : 'http';
        $this->_cache_map['schema'] = $schema;
        return $this->_cache_map['schema'];
    }

    public function client_ip($default = null)
    {
        if (isset($this->_cache_map['client_ip'])) {
            return $this->_cache_map['client_ip'];
        }
        $arr_ip_header = [
            'HTTP_CDN_SRC_IP',
            'HTTP_PROXY_CLIENT_IP',
            'HTTP_WL_PROXY_CLIENT_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        $header = $this->request_header();
        $client_ip = Util::v($header, 'x_forwarded_for', 'unknown');

        foreach ($arr_ip_header as $key) {
            $tmp = $this->_server($key, 'unknown');
            if (!empty($tmp) && strtolower($tmp) != 'unknown') {
                $client_ip = $tmp;
                break;
            }
        }

        $cips = [];
        preg_match("/[\d\.]{7,15}/", $client_ip, $cips);
        $client_ip = !empty($cips[0]) ? $cips[0] : 'unknown';

        if ($client_ip == 'unknown' && !is_null($default)) {
            $client_ip = $default;
        }
        $this->_cache_map['client_ip'] = $client_ip;
        return $this->_cache_map['client_ip'];
    }

    /**
     * 读取原始请求数据
     * @return string
     */
    public function raw_post_data()
    {
        if (isset($this->_cache_map['raw_post_data'])) {
            return $this->_cache_map['raw_post_data'];
        }

        $raw_post_data = file_get_contents('php://input');
        $raw_post_data = !empty($raw_post_data) ? $raw_post_data : '';
        $this->_cache_map['raw_post_data'] = $raw_post_data;
        return $this->_cache_map['raw_post_data'];
    }

    /**
     * 获取request 头部信息 全部使用小写名字
     * @return array
     */
    public function request_header()
    {
        if (isset($this->_cache_map['request_header'])) {
            return $this->_cache_map['request_header'];
        }
        $server = $this->all_server();
        $header = [];
        if (function_exists('apache_request_headers')) {
            $header = apache_request_headers();
        }
        if (empty($header)) {
            $header = [];
            foreach ($server as $key => $value) {
                if ('HTTP_' == substr($key, 0, 5)) {
                    $header[str_replace('_', '-', substr($key, 5))] = $value;
                }
            }
        }

        if (isset($server['PHP_AUTH_DIGEST'])) {
            $header['AUTHORIZATION'] = $server['PHP_AUTH_DIGEST'];
        } elseif (isset($server['PHP_AUTH_USER']) && isset($server['PHP_AUTH_PW'])) {
            $header['AUTHORIZATION'] = base64_encode($server['PHP_AUTH_USER'] . ':' . $server['PHP_AUTH_PW']);
        }
        if (isset($server['CONTENT_LENGTH'])) {
            $header['CONTENT-LENGTH'] = $server['CONTENT_LENGTH'];
        }
        if (isset($server['CONTENT_TYPE'])) {
            $header['CONTENT-TYPE'] = $server['CONTENT_TYPE'];
        }
        foreach ($header as $key => $item) {
            $header[strtolower($key)] = $item;
        }

        $this->_cache_map['request_header'] = $header;
        return $this->_cache_map['request_header'];
    }

    /**
     * 获取request 头部信息 全部使用小写名字
     * @return array
     */
    public function lower_header()
    {
        $headers = $this->request_header();
        $ret = [];
        foreach ($headers as $key => $val) {
            $ret[strtolower($key)] = $val;
        }
        return $ret;
    }

    /**
     * 根据 HTTP_USER_AGENT 获取客户端浏览器信息
     * @return array 浏览器相关信息 ['name', 'version']
     */
    public function agent_browser()
    {
        if (isset($this->_cache_map['agent_browser'])) {
            return $this->_cache_map['agent_browser'];
        }

        $agent = $this->_server('HTTP_USER_AGENT', '');
        $browser = Util::browser_ver($agent);
        $this->_cache_map['agent_browser'] = $browser;
        return $this->_cache_map['agent_browser'];
    }

    public function is_mobile()
    {
        if (isset($this->_cache_map['is_mobile'])) {
            return $this->_cache_map['is_mobile'];
        }
        $mobile_agents = ['xiaomi', "240x320", "acer", "acoon", "acs-", "abacho", "ahong", "airness", "alcatel", "amoi", "android", "anywhereyougo.com", "applewebkit/525", "applewebkit/532", "asus", "audio", "au-mic", "avantogo", "becker", "benq", "bilbo", "bird", "blackberry", "blazer", "bleu", "cdm-", "compal", "coolpad", "danger", "dbtel", "dopod", "elaine", "eric", "etouch", "fly ", "fly_", "fly-", "go.web", "goodaccess", "gradiente", "grundig", "haier", "hedy", "hitachi", "htc", "huawei", "hutchison", "inno", "ipad", "ipaq", "ipod", "jbrowser", "kddi", "kgt", "kwc", "lenovo", "lg ", "lg2", "lg3", "lg4", "lg5", "lg7", "lg8", "lg9", "lg-", "lge-", "lge9", "longcos", "maemo", "mercator", "meridian", "micromax", "midp", "mini", "mitsu", "mmm", "mmp", "mobi", "mot-", "moto", "nec-", "netfront", "newgen", "nexian", "nf-browser", "nintendo", "nitro", "nokia", "nook", "novarra", "obigo", "palm", "panasonic", "pantech", "philips", "phone", "pg-", "playstation", "pocket", "pt-", "qc-", "qtek", "rover", "sagem", "sama", "samu", "sanyo", "samsung", "sch-", "scooter", "sec-", "sendo", "sgh-", "sharp", "siemens", "sie-", "softbank", "sony", "spice", "sprint", "spv", "symbian", "tablet", "talkabout", "tcl-", "teleca", "telit", "tianyu", "tim-", "toshiba", "tsm", "up.browser", "utec", "utstar", "verykool", "virgin", "vk-", "voda", "voxtel", "vx", "wap", "wellco", "wig browser", "wii", "windows ce", "wireless", "xda", "xde", "zte"];

        $user_agent = $this->_server('HTTP_USER_AGENT', '');
        if (empty($user_agent)) {
            return false;
        }
        $is_mobile = false;
        foreach ($mobile_agents as $device) {//这里把值遍历一遍，用于查找是否有上述字符串出现过
            if (stristr($user_agent, $device)) { //stristr 查找访客端信息是否在上述数组中，不存在即为PC端。
                $is_mobile = true;
                break;
            }
        }

        $this->_cache_map['is_mobile'] = $is_mobile;
        return $this->_cache_map['is_mobile'];
    }

    ##################  PHP HOOK ##################

    /**
     * 动态应用一个配置文件  返回配置 key 数组  动态导入配置工作 依靠 request 完成
     * @param string $config_file 配置文件 绝对路径
     * @return array
     * @throws AppStartUpError
     */
    public static function requireForArray($config_file)
    {
        $config_file = trim($config_file);
        if (empty($config_file)) {
            return [];
        }
        if (!is_file($config_file)) {
            throw new AppStartUpError("requireForArray cannot find {$config_file}");
        }

        $ret = include($config_file);  // 动态引入文件 得到数组 用于读取配置
        return $ret;
    }
}