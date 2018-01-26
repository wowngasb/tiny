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
        $server = $context->getRequest()->all_server();
        if (isset($server['CONTENT_TYPE']) && stripos($server['CONTENT_TYPE'], 'application/json') !== false && $server['REQUEST_METHOD'] == "POST") {
            $json_str = $context->getRequest()->raw_post_data();
            $json = !empty($json_str) ? json_decode($json_str, true) : [];
            $params = array_merge($params, $json);  //补充上$_REQUEST 中的信息
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
        $controller = !empty($routeInfo[1]) ? $routeInfo[1] : 'ApiHub';
        $module = !empty($routeInfo[0]) ? $routeInfo[0] : 'api';
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
            $result = call_user_func_array([$context, $action], $params);
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
        $response->resetBody();
        $code = intval($ex->getCode());  // code 为0 或 无error字段 表示没有错误  code设置为0 会忽略error字段
        $error = Application::dev() ? [
            'Exception' => get_class($ex),
            'code' => $ex->getCode(),
            'message' => $ex->getMessage(),
            'file' => $ex->getFile() . ' [' . $ex->getLine() . ']',
            'trace' => self::_fixTraceInfo($ex->getTrace(), $ex->getFile(), $ex->getLine()),
        ] : [
            'code' => $code,
            'message' => 'traceException',
        ];
        $result = ['code' => $code == 0 ? 500 : $code, 'error' => $error];
        $msg = trim($ex->getMessage());
        $result['msg'] = !empty($msg) ? $msg : 'Exception with empty msg';

        while ($get_previous && !empty($ex) && $ex->getPrevious()) {
            $result['error']['errors'] = isset($result['error']['errors']) ? $result['error']['errors'] : [];
            $ex = $ex->getPrevious();
            $result['error']['errors'][] = Application::dev() ? ['Exception' => get_class($ex), 'code' => $ex->getCode(), 'message' => $ex->getMessage(), 'file' => $ex->getFile() . ' [' . $ex->getLine() . ']'] : ['code' => $ex->getCode(), 'message' => $ex->getMessage()];
        }

        $callback = $request->_get('callback', '');
        $json_str = !empty($callback) ? "{$callback}(" . json_encode($result) . ');' : json_encode($result);
        $response->addHeader('Content-Type: application/json;charset=utf-8', false)->appendBody($json_str);
    }

    protected static function _fixTraceInfo(array $traces, $_file, $_line)
    {
        $ret = [];
        foreach ($traces as $trace) {
            list($args, $class, $file, $function, $line, $type) = Util::vl($trace, [
                'args' => [], 'class' => '', 'file' => $_file, 'function' => 'unknown_func', 'line' => $_line, 'type' => '::'
            ]);
            $arg_list = [];
            foreach ($args as $arg) {
                $arg_list[] = self::_dumpVal($arg);
            }
            $args_str = join(',', $arg_list);
            $ret[] = (!empty($class) ? "{$class}{$type}" : '') . "{$function}({$args_str}) at {$file}:{$line}";
        }
        return $ret;
    }

    protected static function _dumpVal($data, $is_short = false)
    {
        $type = gettype($data);
        switch ($type) {
            case 'NULL':
                return 'null';
            case 'boolean':
                return ($data ? 'true' : 'false');
            case 'integer':
            case 'double':
            case 'float':
                return $data;
            case 'string':
                return '"' . addslashes($data) . '"';
            case 'object':
                $class = get_class($data);
                return "{$class}";
            case 'array':
                if ($is_short) {
                    return "<Array>";
                }
                $output_index_count = 0;
                $output_indexed = array();
                $output_associative = array();
                $idx = 0;
                foreach ($data as $key => $value) {
                    if ($idx >= 5) {
                        $output_indexed[] = '...';
                        $output_associative[] = '...';
                        break;
                    }
                    $output_indexed[] = self::_dumpVal($value, true);
                    $output_associative[] = self::_dumpVal($key, true) . ':' . self::_dumpVal($value, true);
                    if ($output_index_count !== NULL && $output_index_count++ !== $key) {
                        $output_index_count = NULL;
                    }
                    $idx += 1;
                }
                if ($output_index_count !== NULL) {
                    return '[' . implode(',', $output_indexed) . ']';
                } else {
                    return '{' . implode(',', $output_associative) . '}';
                }
            default:
                return '<object>'; // Not supported
        }
    }

}