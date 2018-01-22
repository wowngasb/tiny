<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/24 0024
 * Time: 14:28
 */

namespace Tiny;

use Tiny\Exception\AppStartUpError;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;

/**
 * Class StdRequest
 * 默认 StdRequest 请求参数来源 使用默认 php 的 超全局变量
 * @package Tiny
 */
abstract class StdRequest implements RequestInterface
{
    protected $_request_uri = '/';  // 当前请求的Request URI
    protected $_method = 'GET';  // 当前请求的Method, 对于命令行来说, Method为"CLI"
    protected $_language = ''; // 当前请求的希望接受的语言, 对于Http请求来说, 这个值来自分析请求头Accept-Language. 对于不能鉴别的情况, 这个值为空.
    protected $_routed = false; // 表示当前请求是否已经完成路由 完成后 不可修改路由和参数信息
    protected $_http_referer = '';

    protected $_current_route = '';  // 当前使用的 路由名称 在注册路由时给出的
    protected $_route_info = [];  // 当前 路由信息 [$controller, $action, $module]
    protected $_params = [];  // 匹配到的参数 用于调用 action

    protected $_session_started = false;
    protected $_request_timestamp = null;

    private $_request_header = null;
    private $_agent_browser = null;
    private $_raw_post_data = null;
    private $_is_mobile = null;

    private $_response = null;

    public function __construct()
    {
        $this->_request_timestamp = microtime(true);
        $this->_request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $this->_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';
        $this->_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
        $this->_http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
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
        return $this->_request_timestamp;
    }

    /**
     * @return string
     */
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
        $this->_route_info = [trim($routeInfo[0]), trim($routeInfo[1]), trim($routeInfo[2])];
        return $this;
    }

    /**
     * @return string
     */
    public function getRouteInfoAsUri()
    {
        $arr = $this->getRouteInfo();
        return "{$arr[0]}/{$arr[1]}/{$arr[2]}";
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
     * @return $this
     * @throws AppStartUpError
     */
    public function setParams(array $params)
    {
        $this->_params = $params;
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
     * @param bool $is_routed
     * @return $this
     */
    public function setRouted($is_routed = true)
    {
        $this->_routed = $is_routed;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestUri()
    {
        return $this->_request_uri;
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
            $tag = strval($tag) . ':' . $this->usedMilliSecond() . 'ms';
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
     * 启用 session
     * @return $this
     */
    public function session_start()
    {
        if (!$this->_session_started) {
            session_start();
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
        return $this->_session_started ? session_id($id) : null;
    }

    /**
     * @return $this
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
     * @param string $uri
     * @return StdRequest
     */
    public function copy($uri = null)
    {
        $tmp = clone $this;
        if (!is_null($uri)) {
            $tmp->_request_uri = $uri;
        }
        $tmp->reset_route();
        return $tmp;
    }

    /**
     * @return string
     */
    public function fixRequestPath()
    {
        $tmp = explode('?', $this->_request_uri);
        $path = !empty($tmp[0]) ? $tmp[0] : '/';
        while (strpos($path, '//') !== false) {
            $path = str_replace('//', '/', $path);
        }
        if (substr($path, -1, 1) != '/') {
            $path .= '/';
        }
        return trim($path);
    }

    ###############################################################
    ############  超全局变量 ################
    ###############################################################

    ##################  $_GET ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @return string
     */
    public function _get($name, $default = '', $setBack = false)
    {
        $val = isset($_GET[$name]) ? $_GET[$name] : $default;
        if ($setBack) {
            $_GET[$name] = $val;
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
     * @param string $data
     */
    public function set_get($name, $data)
    {
        $_GET[$name] = $data;
    }

    ##################  $_POST ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @return string
     */
    public function _post($name, $default = '', $setBack = false)
    {
        $val = isset($_POST[$name]) ? $_POST[$name] : $default;
        if ($setBack) {
            $_POST[$name] = $val;
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
     * @param string $data
     */
    public function set_post($name, $data)
    {
        $_POST[$name] = $data;
    }

    ##################  $_ENV ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @return string
     */
    public function _env($name, $default = '', $setBack = false)
    {
        $val = isset($_ENV[$name]) ? $_ENV[$name] : $default;
        if ($setBack) {
            $_ENV[$name] = $val;
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
     * @param string $data
     */
    public function set_env($name, $data)
    {
        $_ENV[$name] = $data;
    }

    ##################  $_SERVER ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @return string
     */
    public function _server($name, $default = '', $setBack = false)
    {
        $val = isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
        if ($setBack) {
            $_SERVER[$name] = $val;
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
     * @param string $data
     */
    public function set_server($name, $data)
    {
        $_SERVER[$name] = $data;
    }

    ##################  $_COOKIE ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @return string
     */
    public function _cookie($name, $default = '', $setBack = false)
    {
        $val = isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
        if ($setBack) {
            $_COOKIE[$name] = $val;
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
     * @param string $data
     */
    public function set_cookie($name, $data)
    {
        $_COOKIE[$name] = $data;
    }

    ##################  $_FILES ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @return string
     */
    public function _files($name, $default = '', $setBack = false)
    {
        $val = isset($_FILES[$name]) ? $_FILES[$name] : $default;
        if ($setBack) {
            $_FILES[$name] = $val;
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
     * @param string $data
     */
    public function set_files($name, $data)
    {
        $_FILES[$name] = $data;
    }

    ##################  $_REQUEST ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @return string
     */
    public function _request($name, $default = '', $setBack = false)
    {
        $val = isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
        if ($setBack) {
            $_REQUEST[$name] = $val;
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
     * @param string $data
     */
    public function set_request($name, $data)
    {
        $_REQUEST[$name] = $data;
    }

    ##################  $_SESSION ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @return string
     */
    public function _session($name, $default = '', $setBack = false)
    {
        $val = isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
        if ($setBack) {
            $_SESSION[$name] = $val;
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
     * @param string $data
     */
    public function set_session($name, $data)
    {
        $_SESSION[$name] = $data;
    }

    ##################  HTTP INFO ##################

    /**
     * 读取原始请求数据
     * @return string
     */
    public function raw_post_data()
    {
        if (is_null($this->_raw_post_data)) {
            $this->_raw_post_data = file_get_contents('php://input') ?: '';
        }
        return $this->_raw_post_data;
    }

    /**
     * 获取request 头部信息 全部使用小写名字
     * @return array
     */
    public function getAllHeader()
    {
        $server = $this->all_server();
        if (!is_null($this->_request_header)) {
            return $this->_request_header;
        }

        if (!function_exists('apache_request_headers')) {
            $header = [];
            $rx_http = '/\AHTTP_/';
            foreach ($server as $key => $val) {
                if (preg_match($rx_http, $key)) {
                    $arh_key = preg_replace($rx_http, '', $key);
                    $rx_matches = explode('_', $arh_key);
                    if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                        foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                        $arh_key = implode('-', $rx_matches);
                    }
                    $arh[$arh_key] = $val;
                }
            }
        } else {
            $header = apache_request_headers();
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

        $this->_request_header = $header;
        return $this->_request_header;
    }

    /**
     * 根据 HTTP_USER_AGENT 获取客户端浏览器信息
     * @return array 浏览器相关信息 ['name', 'version']
     */
    public function agentBrowser()
    {
        if (!is_null($this->_agent_browser)) {
            return $this->_agent_browser;
        }
        $browser = [];
        $agent = $this->_server('HTTP_USER_AGENT', '');
        if (stripos($agent, "Firefox/") > 0) {
            preg_match("/Firefox\/([^;)]+)+/i", $agent, $b);
            $browser[0] = "Firefox";
            $browser[1] = $b[1];  //获取火狐浏览器的版本号
        } elseif (stripos($agent, "Maxthon") > 0) {
            preg_match("/Maxthon\/([\d\.]+)/", $agent, $maxthon);
            $browser[0] = "Maxthon";
            $browser[1] = $maxthon[1];
        } elseif (stripos($agent, "MSIE") > 0) {
            preg_match("/MSIE\s+([^;)]+)+/i", $agent, $ie);
            $browser[0] = "IE";
            $browser[1] = $ie[1];  //获取IE的版本号
        } elseif (stripos($agent, "OPR") > 0) {
            preg_match("/OPR\/([\d\.]+)/", $agent, $opera);
            $browser[0] = "Opera";
            $browser[1] = $opera[1];
        } elseif (stripos($agent, "Edge") > 0) {
            //win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
            preg_match("/Edge\/([\d\.]+)/", $agent, $Edge);
            $browser[0] = "Edge";
            $browser[1] = $Edge[1];
        } elseif (stripos($agent, "Chrome") > 0) {
            preg_match("/Chrome\/([\d\.]+)/", $agent, $google);
            $browser[0] = "Chrome";
            $browser[1] = $google[1];  //获取google chrome的版本号
        } elseif (stripos($agent, 'rv:') > 0 && stripos($agent, 'Gecko') > 0) {
            preg_match("/rv:([\d\.]+)/", $agent, $IE);
            $browser[0] = "IE";
            $browser[1] = $IE[1];
        } else {
            $browser[0] = "UNKNOWN";
            $browser[1] = "";
        }

        $this->_agent_browser = $browser;
        return $this->_agent_browser;
    }

    public function isMobile()
    {
        if (!is_null($this->_is_mobile)) {
            return $this->_is_mobile;
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

        $this->_is_mobile = $is_mobile;
        return $this->_is_mobile;
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