<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/25
 * Time: 14:46
 */

namespace Tiny\Interfaces;


use Tiny\Exception\AppStartUpError;

interface RequestInterface
{
    ###############################################################
    ############  私有属性 getter setter ################
    ###############################################################

    /**
     * @return bool
     */
    public function isSessionStarted();

    /**
     * @return float
     */
    public function getRequestTimestamp();

    /**
     * @return string
     */
    public function getHttpReferer();

    /**
     * @return array
     */
    public function getRouteInfo();

    /**
     * @param array $routeInfo
     * @return $this
     * @throws AppStartUpError
     */
    public function setRouteInfo(array $routeInfo);

    /**
     * @return string
     */
    public function getRouteInfoAsUri();

    /**
     * @return array
     */
    public function getParams();

    /**
     * 设置本次请求入口方法的参数
     * @param array $params
     * @return $this
     * @throws AppStartUpError
     */
    public function setParams(array $params);

    /**
     * @param string $current_route
     * @return $this
     * @throws AppStartUpError
     */
    public function setCurrentRoute($current_route);

    /**
     * @return string
     */
    public function getCurrentRoute();

    /**
     * @return string
     */
    public function getMethod();

    /**
     * @return string
     */
    public function getLanguage();

    /**
     * @return bool
     */
    public function isRouted();

    /**
     * @param bool $is_routed
     * @return $this
     */
    public function setRouted($is_routed = true);

    /**
     * @return string
     */
    public function getRequestUri();

    ###############################################################
    ############  启动及运行相关函数 ################
    ###############################################################

    /**
     * @return int
     */
    public function usedMilliSecond();

    /**
     * @param null $tag
     * @return null|string
     */
    public function debugTag($tag = null);

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
    public function setcookie($name, $value, $expire = 0, $path = "", $domain = "", $secure = false, $httponly = false);

    /**
     * 启用 session
     * @param ResponseInterface $response
     * @return $this
     */
    public function session_start(ResponseInterface $response);

    /**
     * @param null $id
     * @return null|string
     */
    public function session_id($id = null);

    /**
     * 返回当前会话状态
     * @return int
     */
    public function session_status();

    /**
     * @return $this
     * @throws AppStartUpError
     */
    public function reset_route();

    /**
     * @param string $uri
     * @return RequestInterface
     */
    public function copy($uri = null);

    /**
     * @return string
     */
    public function fixRequestPath();

    ###############################################################
    ############  超全局变量 ################
    ###############################################################

    ##################  $_GET ##################

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function _get($name, $default = '');

    /**
     * @return array
     */
    public function all_get();

    /**
     * @param string $name
     * @param string $data
     */
    public function set_get($name, $data);

    ##################  $_POST ##################

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function _post($name, $default = '');

    /**
     * @return array
     */
    public function all_post();

    /**
     * @param string $name
     * @param string $data
     */
    public function set_post($name, $data);

    ##################  $_ENV ##################

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function _env($name, $default = '');

    /**
     * @return array
     */
    public function all_env();

    /**
     * @param string $name
     * @param string $data
     */
    public function set_env($name, $data);

    ##################  $_SERVER ##################

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function _server($name, $default = '');

    /**
     * @return array
     */
    public function all_server();

    /**
     * @param string $name
     * @param string $data
     */
    public function set_server($name, $data);

    ##################  $_COOKIE ##################

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function _cookie($name, $default = '');

    /**
     * @return array
     */
    public function all_cookie();

    /**
     * @param string $name
     * @param string $data
     */
    public function set_cookie($name, $data);

    ##################  $_FILES ##################

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function _files($name, $default = '');

    /**
     * @return array
     */
    public function all_files();

    /**
     * @param string $name
     * @param string $data
     */
    public function set_files($name, $data);

    ##################  $_REQUEST ##################

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function _request($name, $default = '');

    /**
     * @return array
     */
    public function all_request();

    /**
     * @param string $name
     * @param string $data
     */
    public function set_request($name, $data);

    ##################  $_SESSION ##################

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function _session($name, $default = '');

    /**
     * @return array
     */
    public function all_session();

    /**
     * @param string $name
     * @param string $data
     */
    public function set_session($name, $data);

    ##################  HTTP INFO ##################

    /**
     * 读取原始请求数据
     * @return string
     */
    public function raw_post_data();

    /**
     * 获取request 头部信息 全部使用小写名字
     * @return array
     */
    public function getAllHeader();

    /**
     * 根据 HTTP_USER_AGENT 获取客户端浏览器信息
     * @return array 浏览器相关信息 ['name', 'version']
     */
    public function agentBrowser();

    public function isMobile();

    ##################  PHP HOOK ##################

    /**
     * 动态应用一个配置文件  返回配置 key 数组  动态导入配置工作 依靠 request 完成
     * @param string $config_file 配置文件 绝对路径
     * @return array
     * @throws AppStartUpError
     */
    public static function requireForArray($config_file);
}