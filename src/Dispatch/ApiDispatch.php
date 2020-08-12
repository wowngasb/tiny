<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/2 0002
 * Time: 18:11
 */

namespace Tiny\Dispatch;

use Exception;
use Tiny\Abstracts\AbstractApi;
use Tiny\Abstracts\AbstractContext;
use Tiny\Abstracts\AbstractDispatch;
use Tiny\Application;
use Tiny\Exception\AppStartUpError;
use Tiny\Exception\Error;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;
use Tiny\Plugin\ApiHelper;
use Tiny\Util;

class ApiDispatch extends AbstractDispatch
{

    ####################################################################
    ############ 实现 AbstractDispatch 默认 API 分发器 ################
    ####################################################################

    /**
     * 根据对象和方法名 获取 修复后的参数
     * @param AbstractContext $context
     * @param string $action
     * @param array $params
     * @return array
     */
    public static function initMethodParams(AbstractContext $context, $action, array $params)
    {
        $params = !empty($params) && is_array($params) ? $params : [];
        $request = $context->getRequest();
        $server = $request->all_server();
        if (isset($server['CONTENT_TYPE']) && stripos($server['CONTENT_TYPE'], 'application/json') !== false && $server['REQUEST_METHOD'] == "POST") {
            $json_str = $context->getRequest()->raw_post_data();
            $json = !empty($json_str) ? json_decode($json_str, true) : [];
            $json = !empty($json) && is_array($json) ? $json : [];
            $params = array_merge($params, $json);  //补充上$_REQUEST 中的信息
            foreach ($params as $n => $v) {
                $request->set_request($n, $v);
            }
        }
        return parent::initMethodParams($context, $action, $params);
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
        $controller = !empty($routeInfo[1]) ? trim($routeInfo[1]) : 'ApiHub';
        $module = !empty($routeInfo[0]) ? trim($routeInfo[0]) : 'hello';
        $appname = Application::appname();
        $namespace = "\\" . Util::joinNotEmpty("\\", [$appname, $module, $controller]);
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
        $context->_setActionName($action);
        return $context;
    }

    public static function dispatch(AbstractContext $context, $action, array $params)
    {
        $callback = $context->_get('callback', '');
        try {
            /** @var AbstractApi $context */
            $context->getResponse()->ob_start();
            $result = call_user_func_array([$context, $action], $params);
            $context->getResponse()->ob_get_clean();
            if ($result instanceof ResponseInterface) {
                return;
            }

            $result = Util::try2array($result);
            if (!isset($result['code'])) {
                $result['code'] = 0;
            }
            $context->_doneApi($action, $params, $result, $callback);

            $json_str = !empty($callback) ? "{$callback}(" . json_encode($result) . ');' : json_encode($result);
            $context->getResponse()->addHeader('Content-Type: application/json;charset=utf-8', false)->appendBody($json_str);
        } catch (Error $ex1) {
            $context->_exceptApi($action, $params, $ex1, $callback);
            self::traceException($context->getRequest(), $context->getResponse(), $ex1);
        } catch (Exception $ex2) {
            $context->_exceptApi($action, $params, $ex2, $callback);
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
        $log_msg = __METHOD__ . " ex:" . $ex->getMessage() . " <" . get_class($ex) . ">";
        error_log($log_msg);

        $response->resetBody();

        $result = static::_buildExceptionResult($ex, $get_previous);

        $callback = $request->_get('callback', '');
        $json_str = !empty($callback) ? "{$callback}(" . json_encode($result) . ');' : json_encode($result);
        $response->addHeader('Content-Type: application/json;charset=utf-8', false)->appendBody($json_str);
    }


    protected static function _buildExceptionResult(Exception $ex, $get_previous = true)
    {
        $code = intval($ex->getCode());  // code 为0 或 无error字段 表示没有错误  code设置为0 会忽略error字段
        $message = trim($ex->getMessage());
        $message = !empty($message) ? $message : "Exception with code {$code}";
        $message = Util::stri_startwith($message, 'sql') ? "SQL Exception with code {$code}" : $message;

        $error = Application::dev() ? [
            'Exception' => get_class($ex),
            'code' => $ex->getCode(),
            'message' => $message,
            'file' => $ex->getFile() . ' [' . $ex->getLine() . ']',
            'trace' => self::_fixTraceInfo($ex),
        ] : [
            'code' => $code,
            'message' => 'traceException',
        ];
        $result = ['code' => $code == 0 ? 500 : $code, 'error' => $error];
        $msg = $message;
        $result['msg'] = !empty($msg) ? $msg : 'Exception with empty msg';

        while ($get_previous && !empty($ex) && $ex->getPrevious()) {
            $result['error']['errors'] = isset($result['error']['errors']) ? $result['error']['errors'] : [];
            $ex = $ex->getPrevious();
            $result['error']['errors'][] = Application::dev() ? ['Exception' => get_class($ex), 'code' => $ex->getCode(), 'message' => $ex->getMessage(), 'file' => $ex->getFile() . ' [' . $ex->getLine() . ']'] : ['code' => $ex->getCode(), 'message' => $ex->getMessage()];
        }

        return $result;
    }

    protected static function _fixTraceInfo(Exception $ex)
    {
        $base_path = Application::path();
        $ret = [];
        $trace_list = Util::trace_exception($ex, $base_path);
        foreach ($trace_list as $r) {
            list($file_str, $line, $class_str, $function, $args_str) = [Util::v($r, 'file_str', ''), Util::v($r, 'line', ''), Util::v($r, 'class_str', ''), Util::v($r, 'function', ''), Util::v($r, 'args_str', '')];
            $ret[] = "{$file_str}:{$line} - {$class_str}{$function}({$args_str})";
        }
        return $ret;
    }


}