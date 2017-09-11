<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/7
 * Time: 14:03
 */

namespace Tiny\Plugin\graphiql\dispatch;

use Exception;
use Tiny\Abstracts\AbstractContext;
use Tiny\Application;
use Tiny\DispatchInterface;
use Tiny\Func;
use Tiny\Request;
use Tiny\Response;

class GraphiQLDispatch implements DispatchInterface
{

    /**
     * 根据对象和方法名 获取 修复后的参数
     * @param AbstractContext $context
     * @param string $action
     * @param array $params
     * @return array
     */
    public static function initMethodParams(AbstractContext $context, $action, array $params)
    {
        return Application::initMethodParams($context, $action, $params);
    }

    /**
     * 修复并返回 真实需要调用对象的方法名称
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodName(array $routeInfo)
    {
        return 'index';
    }

    /**
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodNamespace(array $routeInfo)
    {
        $controller = !empty($routeInfo[1]) ? Func::trimlower($routeInfo[1]) : 'index';
        $module = !empty($routeInfo[0]) ? Func::trimlower($routeInfo[0]) : 'graphiql';

        return "\\Tiny\\Plugin\\{$module}\\controller\\{$controller}";
    }

    /**
     * 创建需要调用的对象 并检查对象和方法的合法性
     * @param Request $request
     * @param Response $response
     * @param string $namespace
     * @param string $action
     * @return AbstractContext 可返回实现此接口的 其他对象 方便做类型限制
     */
    public static function initMethodContext(Request $request, Response $response, $namespace, $action)
    {
        return Application::initMethodContext($request, $response, $namespace, $action);
    }

    /**
     * 调用分发 渲染输出执行结果  请在方法开头加上 固定流程 调用自身接口  无任何返回值
     * @param AbstractContext $context
     * @param string $action
     * @param array $params
     */
    public static function dispatch(AbstractContext $context, $action, array $params)
    {
        Application::dispatch($context, $action, $params);
    }

    /**
     * 处理异常接口 用于捕获分发过程中的异常
     * @param Request $request
     * @param Response $response
     * @param Exception $ex
     */
    public static function traceException(Request $request, Response $response, Exception $ex)
    {
        Application::traceException($request, $response, $ex);
    }

}