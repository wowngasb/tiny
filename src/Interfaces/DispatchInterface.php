<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/31 0031
 * Time: 17:15
 */

namespace Tiny\Interfaces;


use Exception;
use Tiny\Abstracts\AbstractContext;
use Tiny\Request;
use Tiny\Response;

interface DispatchInterface
{

    /**
     * 根据对象和方法名 获取 修复后的参数
     * @param AbstractContext $context
     * @param string $action
     * @param array $params
     * @return array
     */
    public static function initMethodParams(AbstractContext $context, $action, array $params);

    /**
     * 修复并返回 真实需要调用对象的 方法名称
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodName(array $routeInfo);

    /**
     * 修复并返回 真实需要调用对象的 命名空间
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodNamespace(array $routeInfo);

    /**
     * 创建需要调用的对象 并检查对象和方法的合法性
     * @param Request $request
     * @param Response $response
     * @param string $namespace
     * @param string $action
     * @return AbstractContext 可返回实现此接口的 其他对象 方便做类型限制
     */
    public static function initMethodContext(Request $request, Response $response, $namespace, $action);

    /**
     * 调用分发 渲染输出执行结果  请在方法开头加上 固定流程 调用自身接口  无任何返回值
     * @param AbstractContext $context
     * @param string $action
     * @param array $params
     */
    public static function dispatch(AbstractContext $context, $action, array $params);

    /**
     * 处理异常接口 用于捕获分发过程中的异常
     * @param Request $request
     * @param Response $response
     * @param Exception $ex
     */
    public static function traceException(Request $request, Response $response, Exception $ex);
}