<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/25 0025
 * Time: 12:03
 */

namespace Tiny\Route;


use Tiny\Exception\NotFoundError;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\RouteInterface;
use Tiny\Util;

/**
 * Class DefaultRoute
 * DefaultRoute 是一种简单的路由协议, 他只匹配 uri 前缀 始终返回默认信息
 */
class DefaultRoute implements RouteInterface
{
    private $_base_uri = '';
    private $_default_route_info = ['index', 'index', 'index'];

    public function __construct($base_uri = '/', array $default_route_info = [])
    {
        $base_uri = trim($base_uri);
        $base_uri = Util::str_startwith($base_uri, '/') ? $base_uri : "/{$base_uri}";
        $base_uri = Util::str_endwith($base_uri, '/') ? $base_uri : "{$base_uri}/";
        $this->_base_uri = $base_uri;
        $this->_default_route_info = Util::mergeNotEmpty($this->_default_route_info, $default_route_info);
    }

    /**
     * 获取路由 默认参数 用于url参数不齐全时 补全
     * @return array  $routeInfo [$module, $controller, $action]
     */
    public function defaultRoute()
    {
        return $this->_default_route_info;  // 默认 $routeInfo
    }

    /**
     * 根据请求的 $_method $_request_uri $_language 得出 路由信息 及 参数
     * 匹配成功后 获得 路由信息 及 参数
     * @param RequestInterface $request 请求对象
     * @return array 匹配成功 [ [$module, $controller, $action], $params ]  失败 [null, null]
     * @throws NotFoundError
     */
    public function route(RequestInterface $request)
    {
        $uri_origin = $request->fixRequestPath();
        if (Util::stri_cmp($uri_origin, $this->_base_uri)) {
            return [$this->defaultRoute(), []];
        }
        throw new NotFoundError('not match');
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
        return "{$schema}://{$host}/";
    }
}