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
use Tiny\Exception\AppStartUpError;
use Tiny\Exception\MethodNotAllowedError;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\RouteInterface;
use Tiny\Util;

class FastRoute implements RouteInterface
{

    private $_dispatcher = null;
    private $_cache_file = '';
    private $_default_route_info = ['index', 'index', 'index'];
    private $_buildUrl = null;
    private $_actionPre = null;

    /**
     * FastRoute constructor.
     * @param callable $routeDefinitionCallback
     * @param array $default_route_info
     * @param string $cache_file
     * @param callable|null $actionPre
     * @param callable|null $buildUrl
     */
    public function __construct(callable $routeDefinitionCallback, array $default_route_info = [], $cache_file = '', callable $actionPre = null, callable $buildUrl = null)
    {
        $this->_actionPre = $actionPre;
        $this->_buildUrl = $buildUrl;
        $this->_default_route_info = Util::mergeNotEmpty($this->_default_route_info, $default_route_info);
        if (!empty($cache_file) && !Application::dev()) {
            $this->_cache_file = $cache_file;
            $this->_dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, [
                'cacheFile' => $this->_cache_file
            ]);
        } else {
            $this->_dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback);
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
     * @throws MethodNotAllowedError
     */
    public function route(RequestInterface $request)
    {
        $uri = $request->getRequestUri();
        $httpMethod = $request->getMethod();

        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $this->_dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return [null, null];
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                throw new MethodNotAllowedError("method not allowed uri:{$uri}, method:" . $request->getMethod() . ", allowed:" . join(',', $allowedMethods));
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                if (count($handler) == 3) {
                    $all_request = array_merge($request->all_request(), $vars);
                    return [$handler, $all_request];
                } elseif (count($handler) == 2) {
                    $action = !empty($vars['_action']) ? $vars['_action'] : '';
                    unset($vars['_action']);
                    $handler[2] = $this->_fixAction($request, $handler[1], $action);
                    $handler = Util::mergeNotEmpty($this->_default_route_info, $handler);
                    $all_request = array_merge($request->all_request(), $vars);
                    return [$handler, $all_request];
                } elseif (count($handler) == 1) {
                    $handler[1] = !empty($vars['_controller']) ? $vars['_controller'] : '';
                    $action = !empty($vars['_action']) ? $vars['_action'] : '';
                    unset($vars['_action']);
                    $handler[2] = $this->_fixAction($request, $handler[1], $action);
                    $handler = Util::mergeNotEmpty($this->_default_route_info, $handler);
                    $all_request = array_merge($request->all_request(), $vars);
                    return [$handler, $all_request];
                }
        }
        return [null, null];
    }

    private function _fixAction($request, $controller, $action)
    {
        if (!empty($action) && !is_null($this->_actionPre)) {
            $action = call_user_func_array($this->_actionPre, [$request, $controller, $action]);
        }
        return $action;
    }

    /**
     * 根据 路由信息 及 参数 生成反路由 得到 url
     * @param string $schema uri 协议
     * @param string $host domain
     * @param array $routeInfo 路由信息数组  [$module, $controller, $action]
     * @param array $params 参数数组
     * @return string
     * @throws AppStartUpError
     */
    public function buildUrl($schema, $host, array $routeInfo, array $params = [])
    {
        if (is_null($this->_buildUrl)) {
            throw new AppStartUpError("no callable buildUrl for " . __CLASS__);
        }
        return call_user_func_array($this->_buildUrl, [$schema, $host, $routeInfo, $params]);
    }

    /**
     * @return string
     */
    public function getCacheFile()
    {
        return $this->_cache_file;
    }

}