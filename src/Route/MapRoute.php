<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/28 0028
 * Time: 17:40
 */

namespace Tiny\Route;


use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\RouteInterface;
use Tiny\Util;


/**
 * Class RouteMap
 * RouteMap 是一种简单的路由协议, 它将REQUEST_URI中以'/'分割的节, 组合在一起, 形成一个分层的控制器或者动作的路由结果.
 * 对于请求request_uri为"/ap/foo/bar"
 * $base_uri  '/ap'  $default_module 为  'ap'
 * 则最后得到的路由信息为 ['ap', 'foo', 'bar']
 * $base_uri  不为空时  前缀不匹配的时候, RouteMap 会返回失败, 将路由权交给下一个路由协议.
 * $base_uri  为 '/' 时   RouteMap 会尽可能补全路由信息
 */
class MapRoute implements RouteInterface
{

    private $_base_uri = '';
    private $_default_module = '';
    private $_default_route_info = ['index', 'index', 'index'];

    public function __construct($base_uri = '/', $default_module = '', array $default_route_info = [])
    {
        $base_uri = trim($base_uri);
        $base_uri = Util::str_startwith($base_uri, '/') ? $base_uri : "/{$base_uri}";
        // $base_uri = Util::str_endwith($base_uri, '/') ? $base_uri : "{$base_uri}/";
        $this->_base_uri = $base_uri;
        $this->_default_module = trim($default_module);
        $this->_default_route_info = Util::mergeNotEmpty($this->_default_route_info, $default_route_info);
    }

    /**
     * 根据请求的 $_method $_request_uri $_language 得出 路由信息 及 参数
     * 匹配成功后 获得 路由信息 及 参数  总是可以成功
     * 一般参数应设置到 php 原始 $_GET, $_POST $_REQUEST 中， 保持一致性
     * @param RequestInterface $request 请求对象
     * @return array 匹配成功 [$routeInfo, $params]  失败 [null, null]
     */
    public function route(RequestInterface $request)
    {
        $uri_origin = $request->fixRequestPath();
        if (!Util::stri_startwith($uri_origin, $this->_base_uri . '/') && $uri_origin != $this->_base_uri) {
            return [null, null];
        }

        list($default_module, $default_controller, $default_action) = $this->defaultRoute();
        $uri = str_replace($this->_base_uri, '/', $uri_origin);
        $split_list = Util::splitNotEmpty('/', $uri);
        if (!empty($this->_default_module)) {
            $split_list = [$this->_default_module, isset($split_list[0]) ? $split_list[0] : $default_controller, isset($split_list[1]) ? $split_list[1] : $default_action];
        } else {
            $split_list = [isset($split_list[0]) ? $split_list[0] : $default_module, isset($split_list[1]) ? $split_list[1] : $default_controller, isset($split_list[2]) ? $split_list[2] : $default_action];
        }

        $_uri = "/{$split_list[0]}/{$split_list[1]}/{$split_list[2]}";
        $reg_str = "^\/([A-Za-z0-9_]+)\/([A-Za-z0-9_]+)\/([A-Za-z0-9_\-.]+)";
        $matches = [];
        preg_match("/{$reg_str}/i", $_uri, $matches);

        if (!empty($matches[1]) && !empty($matches[2]) && !empty($matches[3])) {
            $routeInfo = [trim($matches[1]), trim($matches[2]), trim($matches[3])];
            return [$routeInfo, $request->all_request()];
        } else {
            return [null, null];
        }
    }

    /**
     * 根据 路由信息 及 参数 生成反路由 得到 url
     * @param string $schema uri 协议
     * @param string $host domain
     * @param array $routeInfo 路由信息数组  [$module, $controller, $action]
     * @param array $params 参数数组
     * @return string
     */
    public function buildUrl($schema, $host, array $routeInfo, array $params = [])
    {
        list($default_module, $default_controller, $default_action) = $this->defaultRoute();

        $module = !empty($routeInfo[0]) ? trim($routeInfo[0]) : $default_module;
        $controller = !empty($routeInfo[1]) ? trim($routeInfo[1]) : $default_controller;
        $action = !empty($routeInfo[2]) ? trim($routeInfo[2]) : $default_action;

        $url = "{$schema}://{$host}/{$module}/{$controller}/{$action}";
        $args_list = [];
        foreach ($params as $key => $val) {
            $args_list[] = trim($key) . '=' . urlencode($val);
        }
        return !empty($args_list) ? $url . '?' . join('&', $args_list) : $url;
    }

    /**
     * 获取路由 默认参数 用于url参数不齐全时 补全
     * @return array  $routeInfo [$module, $controller, $action]
     */
    public function defaultRoute()
    {
        return $this->_default_route_info;  // 默认 $routeInfo
    }
}