<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/2 0002
 * Time: 18:11
 */

namespace Tiny\Dispatch;


use Tiny\Abstracts\AbstractApi;
use Exception;
use Tiny\Application;
use Tiny\Abstracts\AbstractContext;
use Tiny\Interfaces\DispatchInterface;
use Tiny\Exception\AppStartUpError;
use Tiny\Exception\Error;
use Tiny\Func;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;
use Tiny\Plugin\ApiHelper;
use Tiny\Traits\CacheTrait;
use Tiny\Traits\LogTrait;

class ApiDispatch implements DispatchInterface
{
    use LogTrait, CacheTrait;

    /**
     * 根据对象和方法名 获取 修复后的参数
     * @param AbstractContext $context
     * @param string $action
     * @param array $params
     * @return array
     */
    public static function initMethodParams(AbstractContext $context, $action, array $params)
    {
        $__server = $context->_server();
        if (isset($__server['CONTENT_TYPE']) && stripos($__server['CONTENT_TYPE'], 'application/json') !== false && $__server['REQUEST_METHOD'] == "POST") {
            $json_str = $context->getRequest()->raw_post_data();
            $json = !empty($json_str) ? json_decode($json_str, true) : [];
            $params = array_merge($params, $json);  //补充上$_REQUEST 中的信息
        }
        return Application::initMethodParams($context, $action, $params);
    }

    /**
     * 修复并返回 真实需要调用对象的方法名称
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodName(array $routeInfo)
    {
        return $routeInfo[2];
    }

    /**
     * 修复并返回 真实需要调用对象的 命名空间
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodNamespace(array $routeInfo)
    {
        $controller = !empty($routeInfo[1]) ? Func::trimlower($routeInfo[1]) : 'ApiHub';
        $module = !empty($routeInfo[0]) ? Func::trimlower($routeInfo[0]) : 'api';
        $appname = Application::app()->getAppName();
        $namespace = "\\" . Func::joinNotEmpty("\\", [$appname, $module, $controller]);
        return $namespace;
    }

    /**
     * 创建需要调用的对象 并检查对象和方法的合法性
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param string $namespace
     * @param string $action
     * @return AbstractContext
     * @throws AppStartUpError
     */
    public static function initMethodContext(RequestInterface $request, ResponseInterface $response, $namespace, $action)
    {
        if (!class_exists($namespace)) {
            throw new AppStartUpError("class:{$namespace} not exists with {$namespace}");
        }
        $context = new $namespace($request, $response);
        if (!($context instanceof AbstractApi)) {
            throw new AppStartUpError("class:{$namespace} isn't instanceof AbstractApi with {$namespace}");
        }
        if (!is_callable([$context, $action]) || ApiHelper::isIgnoreMethod($action)) {
            throw new AppStartUpError("action:{$namespace}::{$action} not allowed with {$namespace}");
        }
        $context->setActionName($action);
        return $context;
    }

    public static function dispatch(AbstractContext $context, $action, array $params)
    {
        $callback = $context->_get('callback', '');
        try {
            /** @var AbstractApi $context */
            $result = call_user_func_array([$context, $action], $params);
            $context->doneApi($action, $params, $result, $callback);

            $json_str = !empty($callback) ? "{$callback}(" . json_encode($result) . ');' : json_encode($result);
            $context->getResponse()->addHeader('Content-Type: application/json;charset=utf-8', false)->appendBody($json_str);
        } catch (Error $ex1) {
            $context->exceptApi($action, $params, $ex1, $callback);
            self::traceException($context->getRequest(), $context->getResponse(), $ex1);
        } catch (Exception $ex2) {
            $context->exceptApi($action, $params, $ex2, $callback);
            self::traceException($context->getRequest(), $context->getResponse(), $ex2);
        }
    }

    /**
     * 处理异常接口 用于捕获分发过程中的异常
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param Exception $ex
     * @param bool $get_previous
     * @throws AppStartUpError
     */
    public static function traceException(RequestInterface $request, ResponseInterface $response, Exception $ex, $get_previous = true)
    {
        $response->clearBody();
        $code = $ex->getCode();  // errno为0 或 无error字段 表示没有错误  errno设置为0 会忽略error字段
        $error = Application::dev() ? [
            'Exception' => get_class($ex),
            'code' => $ex->getCode(),
            'message' => $ex->getMessage(),
            'file' => $ex->getFile() . ' [' . $ex->getLine() . ']',
        ] : [
            'code' => $code,
            'message' => $ex->getMessage(),
        ];
        $result = ['errno' => $code == 0 ? -1 : $code, 'error' => $error];
        $result['FlagString'] = '服务异常:' . $ex->getMessage();

        while ($get_previous && !empty($ex) && $ex->getPrevious()) {
            $result['error']['errors'] = isset($result['error']['errors']) ? $result['error']['errors'] : [];
            $ex = $ex->getPrevious();
            $result['error']['errors'][] = Application::dev() ? ['Exception' => get_class($ex), 'code' => $ex->getCode(), 'message' => $ex->getMessage(), 'file' => $ex->getFile() . ' [' . $ex->getLine() . ']'] : ['code' => $ex->getCode(), 'message' => $ex->getMessage()];
        }

        $callback = $request->_get('callback', '');
        $json_str = !empty($callback) ? "{$callback}(" . json_encode($result) . ');' : json_encode($result);
        $response->addHeader('Content-Type: application/json;charset=utf-8', false)->appendBody($json_str);
    }
}