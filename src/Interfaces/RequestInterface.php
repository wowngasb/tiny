<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/25
 * Time: 14:46
 */

namespace Tiny\Interfaces;


use Tiny\Exception\AppStartUpError;
use Tiny\Plugin\UploadedFile;

interface RequestInterface
{
    ###############################################################
    ############  私有属性 getter setter ################
    ###############################################################

    /**
     * 绑定 ResponseInterface
     * @param ResponseInterface $response
     */
    public function bindingResponse(ResponseInterface $response);

    /**
     * @return ResponseInterface
     */
    public function getBindingResponse();

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
     * @return RequestInterface
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
     * @return RequestInterface
     * @throws AppStartUpError
     */
    public function setParams(array $params);

    /**
     * @param string $current_route
     * @return RequestInterface
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
     * @return array
     */
    public function getLanguages();

    /**
     * @return bool
     */
    public function isRouted();

    /**
     * @param bool $is_routed
     * @return RequestInterface
     */
    public function setRouted($is_routed = true);

    /**
     * @return string
     */
    public function getRequestUri();

    /**
     * Generates the normalized query string for the Request.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized
     * and have consistent escaping.
     *
     * @return string|null A normalized query string for the Request
     */
    public function getQueryString();

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
     * @return RequestInterface
     */
    public function session_start();

    /**
     * 设置 session 保存 句柄
     * @param \SessionHandlerInterface $sessionhandler
     * @param bool $register_shutdown
     * @return RequestInterface
     */
    public function session_set_save_handler(\SessionHandlerInterface $sessionhandler, $register_shutdown = true);

    public function session_name($name = null);

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
     * @return RequestInterface
     * @throws AppStartUpError
     */
    public function reset_route();

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
     * @param bool $setBack
     * @param bool $popKey
     * @return string|array
     */
    public function _get($name, $default = '', $setBack = false, $popKey = false);

    /**
     * @return array
     */
    public function all_get();

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_get($name, $data);

    /**
     * @param string $name
     */
    public function del_get($name);

    ##################  $_POST ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string|array
     */
    public function _post($name, $default = '', $setBack = false, $popKey = false);

    /**
     * @return array
     */
    public function all_post();

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_post($name, $data);

    /**
     * @param string $name
     */
    public function del_post($name);

    ##################  $_ENV ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string|array
     */
    public function _env($name, $default = '', $setBack = false, $popKey = false);

    /**
     * @return array
     */
    public function all_env();

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_env($name, $data);

    /**
     * @param string $name
     */
    public function del_env($name);

    ##################  $_SERVER ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string|array
     */
    public function _server($name, $default = '', $setBack = false, $popKey = false);

    /**
     * @return array
     */
    public function all_server();

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_server($name, $data);


    /**
     * @param string $name
     */
    public function del_server($name);

    ##################  $_COOKIE ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string|array
     */
    public function _cookie($name, $default = '', $setBack = false, $popKey = false);

    /**
     * @return array
     */
    public function all_cookie();

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_cookie($name, $data);

    /**
     * @param string $name
     */
    public function del_cookie($name);

    ##################  $_FILES ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string|array
     */
    public function _files($name, $default = '', $setBack = false, $popKey = false);

    /**
     * @return array
     */
    public function all_files();

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_files($name, $data);

    /**
     * @param string $name
     */
    public function del_files($name);

    ##################  $_REQUEST ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string|array
     */
    public function _request($name, $default = '', $setBack = false, $popKey = false);

    /**
     * @return array
     */
    public function all_request();

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_request($name, $data);

    /**
     * @param string $name
     */
    public function del_request($name);

    ##################  $_SESSION ##################

    /**
     * @param string $name
     * @param string $default
     * @param bool $setBack
     * @param bool $popKey
     * @return string|array
     */
    public function _session($name, $default = '', $setBack = false, $popKey = false);

    /**
     * @return array
     */
    public function all_session();

    /**
     * @param string $name
     * @param string|array $data
     */
    public function set_session($name, $data);

    /**
     * @param string $name
     */
    public function del_session($name);

    ###############################################################
    ######################  文件处理相关 ##########################
    ###############################################################

    /**
     * Get an array of all of the files on the request.
     *
     * @return array
     */
    public function allFiles();

    /**
     * Retrieve a file from the request.
     *
     * @param  string $key
     * @param  mixed $default
     * @return UploadedFile|array|null
     */
    public function file($key = null, $default = null);

    /**
     * Determine if the uploaded data contains a file.
     *
     * @param  string $key
     * @return bool
     */
    public function hasFile($key);

    ###############################################################
    ############  测试相关 可以伪造 请求的各种参数 ################
    ###############################################################

    /**
     * @param string $method
     * @param string $uri
     * @param array $args
     * @return self
     */
    public function copyHttpArgs($method = null, $uri = null, array $args = []);

    public function hookHttpArgs(array $args = []);

    public function resetHttpArgs();

    /**
     * 获取 完整 url
     * @return string
     */
    public function full();

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function _header($name, $default = '');

    ##################  HTTP INFO ##################

    /**
     * 判断是否 https
     * @return bool
     */
    public function is_https();

    public function path();

    public function ajax();

    public function host();

    public function schema();

    /**
     * 获取客户端 ip  无法获取时 默认返回  unknown
     * @param string $default 默认为  unknown  可以设置 其他默认值
     * @return string
     */
    public function client_ip($default = null);


    /**
     * 读取原始请求数据
     * @return string
     */
    public function raw_post_data();

    /**
     * 获取request 头部信息 大小写混合 全部使用小写名字
     * @return array
     */
    public function request_header();

    /**
     * 获取request 头部信息 全部使用小写名字
     * @return array
     */
    public function lower_header();

    /**
     * 根据 HTTP_USER_AGENT 获取客户端浏览器信息
     * @return array 浏览器相关信息 ['name', 'version']
     */
    public function agent_browser();

    public function is_mobile();

    ##################  PHP HOOK ##################

    /**
     * 动态应用一个配置文件  返回配置 key 数组  动态导入配置工作 依靠 request 完成
     * @param string $config_file 配置文件 绝对路径
     * @return array
     * @throws AppStartUpError
     */
    public static function requireForArray($config_file);
}