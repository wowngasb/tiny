<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/24 0024
 * Time: 15:08
 */

namespace Tiny\Interfaces;


/**
 * 路由接口类  实现此接口的可用于 路由分发
 * Interface RouteInterface
 * @package Tiny
 */
interface RouteInterface
{

    /**
     * 获取路由 默认参数 用于url参数不齐全时 补全
     * @return array  $routeInfo [$module, $controller, $action]
     */
    public function defaultRoute();

    /**
     * 根据请求的 $_method $_request_uri $_language 得出 路由信息 及 参数
     * 匹配成功后 获得 路由信息 及 参数
     * @param RequestInterface $request 请求对象
     * @return array 匹配成功 [ [$module, $controller, $action], $params ]  失败 [null, null]
     */
    public function route(RequestInterface $request);

    /**
     * 根据 路由信息 及 参数 生成反路由 得到 url
     * @param string $schema uri 协议
     * @param string $host domain
     * @param array $routeInfo 路由信息数组  [$module, $controller, $action]
     * @param array $params 参数数组
     * @return string
     */
    public function buildUrl($schema, $host, array $routeInfo, array $params = []);

}