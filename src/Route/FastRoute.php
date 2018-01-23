<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/23 0023
 * Time: 14:26
 */

namespace Tiny\Route;

use FastRoute\Dispatcher;
use Tiny\Application;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\RouteInterface;
use Tiny\Util;

class FastRoute implements RouteInterface
{

    private $dispatcher = null;
    private $cache_file = '';
    private $_default_route_info = ['index', 'index', 'index'];

    /**
     * FastRoute constructor.
     * @param callable $routeDefinitionCallback
     * @param array $default_route_info
     * @param string $cache_file
     */
    public function __construct(callable $routeDefinitionCallback, array $default_route_info = [], $cache_file = '')
    {
        $this->_default_route_info = Util::mergeNotEmpty($this->_default_route_info, $default_route_info);
        if (!empty($cache_file) && !Application::dev()) {
            $this->cache_file = $cache_file;
            $this->dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, [
                'cacheFile' => $this->cache_file
            ]);
        } else {
            $this->dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback);
        }
    }

    /**
     * 获取路由 默认参数 用于url参数不齐全时 补全
     * @return array  $routeInfo [$module, $controller, $action]
     */
    public function defaultRoute()
    {
        return $this->_default_route_info;
    }

    /**
     * 根据请求的 $_method $_request_uri $_language 得出 路由信息 及 参数
     * 匹配成功后 获得 路由信息 及 参数
     * @param RequestInterface $request 请求对象
     * @return array 匹配成功 [ [$module, $controller, $action], $params ]  失败 [null, null]
     */
    public function route(RequestInterface $request)
    {
        $uri = $request->getRequestUri();
        $httpMethod = $request->getMethod();

        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return [null, null];
            case Dispatcher::METHOD_NOT_ALLOWED:
                // $allowedMethods = $routeInfo[1];
                return [null, null];
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                if (count($handler) == 3) {
                    return [$handler, $request->all_request()];
                } elseif (count($handler) == 2) {
                    $handler[2] = !empty($vars['action']) ? $vars['action'] : '';
                    $handler = Util::mergeNotEmpty($this->_default_route_info, $handler);
                    return [$handler, $request->all_request()];
                } elseif (count($handler) == 1) {
                    $handler[1] = !empty($vars['controller']) ? $vars['controller'] : '';
                    $handler[2] = !empty($vars['action']) ? $vars['action'] : '';
                    $handler = Util::mergeNotEmpty($this->_default_route_info, $handler);
                    return [$handler, $request->all_request()];
                }
        }
        return [null, null];
    }

    /**
     * 根据 路由信息 及 参数 生成反路由 得到 url
     * @param array $routeInfo 路由信息数组  [$module, $controller, $action]
     * @param array $params 参数数组
     * @return string
     */
    public function buildUrl(array $routeInfo, array $params = [])
    {
        return '';
    }

}