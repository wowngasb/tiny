<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/25 0025
 * Time: 18:34
 */

namespace Tiny\Route;

use Tiny\Exception\AppStartUpError;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\RouteInterface;
use Tiny\Util;


/**
 * Class RouteSupervar
 * RouteSupervar和RouteSimple相似, 都是在query string中获取路由信息, 不同的是, 它获取的是一个类似包含整个路由信息的request_uri
 * 在query string中不包含 $route_key 变量的时候, RouteSupervar 会返回失败, 将路由权交给下一个路由协议.
 * $route = new RouteSupervar("r");
 * $app->addRoute("name", $route);
 * 对于如下请求: "http://domain.com/index.php?r=a/b/c   能得到如下路由结果
 *  $routeInfo = ['a', 'b', 'c']
 *  unset($params[$this->route_key]);
 *  $params = $_REQUEST;
 * @package Tiny
 */
class SupervarRoute implements RouteInterface
{

    private $route_key = 'r';
    private $_default_route_info = ['index', 'index', 'index'];

    public function __construct($route_key = 'r', array $default_route_info = [])
    {
        if (empty($route_key)) {
            throw new AppStartUpError(__CLASS__ . ' some key empty');
        }
        $this->route_key = trim($route_key);
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
        list($default_module, $default_controller, $default_action) = $this->defaultRoute();
        $route_value = $request->_get($this->route_key, '');
        if (empty($route_value)) {
            return [null, null];
        }

        while (strpos($route_value, '//') !== false) {
            $route_value = str_replace('//', '/', $route_value);
        }

        $route_value = substr($route_value, 0, 1) == '/' ? substr($route_value, 1) : $route_value;
        $route_array = explode('/', $route_value);
        if (count($route_array) >= 3) {
            $module = !empty($route_array[0]) ? trim($route_array[0]) : $default_module;
            $controller = !empty($route_array[1]) ? trim($route_array[1]) : $default_controller;
            $action = !empty($route_array[2]) ? trim($route_array[2]) : $default_action;
        } else {
            $controller = !empty($route_array[0]) ? trim($route_array[0]) : $default_controller;
            $action = !empty($route_array[1]) ? trim($route_array[1]) : $default_action;
            $module = $default_module;
        }

        $routeInfo = [$controller, $action, $module];
        $params = $request->all_request();
        unset($params[$this->route_key]);
        return [$routeInfo, $params];
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
        unset($params[$this->route_key]);
        $controller = !empty($routeInfo[1]) ? trim($routeInfo[1]) : $default_controller;
        $action = !empty($routeInfo[2]) ? trim($routeInfo[2]) : $default_action;
        $module = !empty($routeInfo[0]) ? trim($routeInfo[0]) : $default_module;

        $url = "{$schema}://{$host}/index.php";
        $route_value = "{$module}/{$controller}/{$action}";

        $args_list = [];
        $args_list[] = "{$this->route_key}={$route_value}";
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