<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/10/24
 * Time: 16:32
 */

namespace Tiny\Abstracts;


use Exception;
use Tiny\Application;
use Tiny\Exception\NotFoundError;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;
use Tiny\Plugin\ApiHelper;
use Tiny\Util;

/**
 * Class AbstractDispatch
 * 分发器 虚类 已默认实现 分发页面相关逻辑
 * @package Tiny\Abstracts
 */
abstract class AbstractDispatch extends AbstractClass
{

    ##############################################################
    ############ 实现 AbstractDispatch 默认分发器 ################
    ##############################################################

    /**
     * 根据对象和方法名 获取 修复后的参数
     * @param AbstractContext $context
     * @param $action
     * @param array $params
     * @return array
     */
    public static function initMethodParams(AbstractContext $context, $action, array $params)
    {
        $params = ApiHelper::fixActionParams($context, $action, $params);
        $params = $context->beforeAction($params);
        $context->getRequest()->setParams($params);
        return $params;
    }

    /**
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodName(array $routeInfo)
    {
        return Util::trimlower($routeInfo[2]);
    }

    /**
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodNamespace(array $routeInfo)
    {
        $controller = !empty($routeInfo[1]) ? Util::trimlower($routeInfo[1]) : 'index';
        $module = !empty($routeInfo[0]) ? Util::trimlower($routeInfo[0]) : 'index';
        $appname = Application::app()->getAppName();
        return "\\" . Util::joinNotEmpty("\\", [$appname, $module, 'controller', $controller]);
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param string $namespace
     * @param string $action
     * @return AbstractContext
     * @throws NotFoundError
     */
    public static function initMethodContext(RequestInterface $request, ResponseInterface $response, $namespace, $action)
    {
        if (!class_exists($namespace)) {
            throw new NotFoundError("class:{$namespace} not exists with {$namespace}");
        }
        $context = new $namespace($request, $response);
        if (!($context instanceof AbstractController)) {
            throw new NotFoundError("class:{$namespace} isn't instanceof AbstractController with {$namespace}");
        }
        if (!is_callable([$context, $action])) {
            throw new NotFoundError("action:{$namespace}::{$action} not callable with {$namespace}");
        }
        $context->_setActionName($action);
        return $context;
    }

    /**
     * @param AbstractContext $context
     * @param string $action
     * @param array $params
     */
    public static function dispatch(AbstractContext $context, $action, array $params)
    {
        $context->getResponse()->ob_start();
        call_user_func_array([$context, $action], $params);
        $string_buffer = $context->getResponse()->ob_get_clean();

        if (!empty($string_buffer)) {
            $context->getResponse()->appendBody($string_buffer);
        }
    }

    /**
     * 处理异常接口 用于捕获分发过程中的异常
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param Exception $ex
     */
    public static function traceException(RequestInterface $request, ResponseInterface $response, Exception $ex)
    {
        false && func_get_args();
        if ($ex instanceof NotFoundError) {
            static::traceNotFound($request, $response, $ex);
        }
        $code = $ex->getCode();
        $http_code = $code >= 500 && $code < 600 ? $code : 500;
        $msg = Application::dev() ? "<p>Exception:</p>" . get_class($ex) . "<p>Message:</p>" . $ex->getMessage() . "<p>Request:</p><pre>" . print_r($request, true) . "</pre><p>Trace:</p><pre>" . $ex->getTraceAsString() . "</pre>" : 'Exception';
        $response->resetResponse()->setResponseCode($http_code)->appendBody($msg)->end();
    }

    public static function traceNotFound(RequestInterface $request, ResponseInterface $response, Exception $ex)
    {
        $http_code = $ex->getCode(); // 404
        $msg = Application::dev() ? 'Page Not Found:' . $request->fixRequestPath() : 'Page NotFound';
        $response->resetResponse()->setResponseCode($http_code)->appendBody($msg)->end();
    }

}