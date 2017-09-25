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

    public function getRequestTimestamp();

    public function getCsrfToken();

    public function getThisUrl();

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

    public function usedMilliSecond();

    public function debugTag($tag = null);

    public function setcookie($name, $value, $expire = 0, $path = "", $domain = "", $secure = false, $httponly = false);

    /**
     * 启用 session
     * @param ResponseInterface $response
     * @return $this
     */
    public function session_start(ResponseInterface $response);

    public function session_id($id = null);

    /**
     * @return $this
     * @throws AppStartUpError
     */
    public function reset_route();

    /**
     * @param string $uri
     * @return ResponseInterface
     */
    public function copy($uri = null);

    /**
     * @return string
     */
    public function fixRequestPath();

    /**
     * 根据 路由信息 和 参数 按照路由规则生成 url
     * @param RequestInterface $request
     * @param array $routerArr 格式为 [$module, $controller, $action] 使用当前相同 设置为空即可
     * @param array $params
     * @return string
     * @throws AppStartUpError
     */
    public static function urlTo(RequestInterface $request, array $routerArr = [], array $params = []);

    ###############################################################
    ############  超全局变量 ################
    ###############################################################

    /**
     * @param string $name
     * @param string $default
     * @return string|array
     */
    public function _get($name = null, $default = '');

    public function set_get($name, $data);

    /**
     * @param $name
     * @param string $default
     * @return string|array
     */
    public function _post($name = null, $default = '');

    public function set_post($name, $data);

    /**
     * @param $name
     * @param string $default
     * @return string|array
     */
    public function _env($name = null, $default = '');

    public function set_env($name, $data);

    /**
     * @param $name
     * @param string $default
     * @return string|array
     */
    public function _server($name = null, $default = '');

    public function set_server($name, $data);

    /**
     * @param $name
     * @param string $default
     * @return string|array
     */
    public function _cookie($name = null, $default = '');

    public function set_cookie($name, $data);

    /**
     * @param $name
     * @param string $default
     * @return string|array
     */
    public function _files($name = null, $default = '');

    public function set_files($name, $data);

    /**
     * @param $name
     * @param string $default
     * @return string|array
     */
    public function _request($name = null, $default = '');

    public function set_request($name, $data);

    /**
     * @param $name
     * @param string $default
     * @return string|array
     */
    public function _session($name = null, $default = '');

    public function set_session($name, $data);
}