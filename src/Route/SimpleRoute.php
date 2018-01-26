<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/25 0025
 * Time: 9:04
 */

namespace Tiny\Route;

use Tiny\Exception\AppStartUpError;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\RouteInterface;
use Tiny\Util;

/**
 * Class RouteSimple
 * RouteSimple是基于请求中的query string来做路由的, 在初始化一个RouteSimple路由协议的时候, 我们需要给出3个参数, 这3个参数分别代表在query string中module, Controller, Action的变量名:
 * 只有在query string中不包含任何3个参数之一的情况下, RouteSimple 才会返回失败, 将路由权交给下一个路由协议.
 *  $route = new RouteSimple("m", "c", "a");
 *  $app->addRoute("name", $route);
 *  对于如下请求: "http://domain.com/index.php?c=index&a=test 能得到如下路由结果
 *  $routeInfo = [self::$default_module, 'index', 'test']
 * unset($params[$this->module_key], $params[$this->controller_key], $params[$this->action_key]);
 * $params = $_REQUEST;
 * @package Tiny
 */
class SimpleRoute implements RouteInterface
{

    private $module_key = '';
    private $controller_key = '';
    private $action_key = '';
    private $_default_route_info = ['index', 'index', 'index'];

    public function __construct($module_key = 'g', $controller_key = 'c', $action_key = 'a', array $default_route_info = [])
    {
        if (empty($module_key) || empty($controller_key) || empty($action_key)) {
            throw new AppStartUpError(__CLASS__ . ' some key empty');
        }
        list($this->module_key, $this->controller_key, $this->action_key) = [trim($module_key), trim($controller_key), trim($action_key)];

        $this->_default_route_info = Util::mergeNotEmpty($this->_default_route_info, $default_route_info);
    }

    /**
     * 根据请求的 $_method $_request_uri $_language 得出 路由信息 及 参数
     * 匹配成功后 获得 路由信息 及 参数
     * @param RequestInterface $request 请求对象
     * @return array 匹配成功 [$routeInfo, $params]  失败 [null, null]
     */
    public function route(RequestInterface $request)
    {
        $module = $request->_get($this->module_key, '');
        $controller = $request->_get($this->controller_key, '');
        $action = $request->_get($this->action_key, '');
        if (empty($controller) && empty($action) && empty($module)) {
            return [null, null];
        }
        list($default_module, $default_controller, $default_action) = $this->defaultRoute();
        $module = !empty($module) ? trim($module) : $default_module;
        $controller = !empty($controller) ? trim($controller) : $default_controller;
        $action = !empty($action) ? trim($action) : $default_action;

        $routeInfo = [$module, $controller, $action];
        $params = $request->all_request();
        unset($params[$this->module_key], $params[$this->controller_key], $params[$this->action_key]);
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
        unset($params[$this->module_key], $params[$this->controller_key], $params[$this->action_key]);
        $module = !empty($routeInfo[0]) ? trim($routeInfo[0]) : $default_module;
        $controller = !empty($routeInfo[1]) ? trim($routeInfo[1]) : $default_controller;
        $action = !empty($routeInfo[2]) ? trim($routeInfo[2]) : $default_action;

        $url = "{$schema}://{$host}/index.php";
        $args_list = [];
        $args_list[] = "{$this->module_key}={$module}";
        $args_list[] = "{$this->controller_key}={$controller}";
        $args_list[] = "{$this->action_key}={$action}";
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