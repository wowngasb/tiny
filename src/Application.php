<?php
namespace Tiny;

use Exception;
use Tiny\Abstracts\AbstractContext;
use Tiny\Abstracts\AbstractController;
use Tiny\Exception\AppStartUpError;
use Tiny\Plugin\ApiHelper;
use Tiny\Traits\EventTrait;

/**
 * Class Application
 * @package Tiny
 */
final class Application implements DispatchInterface, RouteInterface
{
    use EventTrait;

    private $_config = [];  // 全局配置
    private $_app_name = 'app';  // app 目录，用于 拼接命名空间 和 定位模板文件
    private $_route_name = 'default';  // 默认路由名字，总是会路由到 index
    private $_default_route_info = ['index', 'index', 'index'];

    private $_bootstrap_completed = false;  // 布尔值, 指明当前的Application是否已经运行
    private $_routes = [];  // 路由列表
    private $_dispatches = [];  // 分发列表

    private static $_instance = null;  // Application实现单利模式, 此属性保存当前实例

    /**
     * Application constructor.
     * @param array $config 关联数组的配置
     */
    public function __construct(array $config = [])
    {
        $this->_config = $config;
        self::$_instance = $this;
    }

    /**
     * 获取当前的Application实例
     * @return Application
     */
    public static function app()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public static function usedMilliSecond()
    {
        return round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) * 1000;
    }

    public function setBootstrapCompleted()
    {
        $this->_bootstrap_completed = true;
    }

    public function isBootstrapCompleted()
    {
        return $this->_bootstrap_completed;
    }

    /**
     * 获取 全局配置 数组
     * @param void
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * 获取 全局配置 指定key的值 不存在则返回 default
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getEnv($key, $default = '')
    {
        return isset($this->_config[$key]) ? $this->_config[$key] : $default;
    }

    /**
     * @param $appname
     * @return $this
     * @throws AppStartUpError
     */
    public function setAppName($appname)
    {
        if ($this->_bootstrap_completed) {
            throw new AppStartUpError('cannot setAppName after bootstrap completed');
        }

        $this->_app_name = $appname;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_app_name;
    }

    /**
     * 运行一个Application, 开始接受并处理请求. 这个方法只能成功调用一次.
     * @throws AppStartUpError
     */
    public function run()
    {
        if (!$this->_bootstrap_completed) {
            throw new AppStartUpError('cannot run Application before bootstrap completed');
        }
        $request = new Request();
        $response = new Response();
        static::fire('routerStartup', [$this, $request, $response]);  // 在路由之前触发	这个是7个事件中, 最早的一个. 但是一些全局自定的工作, 还是应该放在Bootstrap中去完成

        list($route, list($routeInfo, $params)) = $this->route($request);  // 必定会 匹配到一条路由  默认路由 default=>Application 始终会定向到 index/index->index()

        $request->setUnRouted()
            ->setCurrentRoute($route)
            ->setRouteInfo($routeInfo)
            ->setParams($params)
            ->setRouted();

        static::fire('routerShutdown', [$this, $request, $response]);  // 路由结束之后触发	此时路由一定正确完成, 否则这个事件不会触发
        static::fire('dispatchLoopStartup', [$this, $request, $response]);  // 分发循环开始之前被触发
        static::forward($request, $response, $routeInfo, $params, $route);
        static::fire('dispatchLoopShutdown', [$this, $request, $response]);  // 分发循环结束之后触发	此时表示所有的业务逻辑都已经运行完成, 但是响应还没有发送

        $response->sendBody();
    }

    /**
     * 根据路由信息 dispatch 执行指定 Action 获得缓冲区输出 丢弃函数返回结果  会影响 $request 实例
     * @param Request $request
     * @param Response $response
     * @param array $routeInfo 格式为 [$module, $controller, $action] 使用当前相同 设置为空即可
     * @param array|null $params
     * @param string|null $route
     * @throws AppStartUpError
     */
    public static function forward(Request $request, Response $response, array $routeInfo = [], array $params = null, $route = null)
    {
        $routeInfo = Func::mergeNotEmpty($request->getRouteInfo(), $routeInfo);
        $app = self::app();
        // 对使用默认值 null 的参数 用当前值补全
        if (is_null($route)) {
            $route = $request->getCurrentRoute();
        }
        $app->getRoute($route);  // 检查对应 route 是否注册过
        if (is_null($params)) {
            $params = $request->getParams();
        }

        $request->setUnRouted()
            ->setCurrentRoute($route)
            ->setRouteInfo($routeInfo)
            ->setRouted();  // 根据新的参数 再次设置 $request 的路由信息
        // 设置完成 锁定 $request

        $response->resetResponse();  // 清空已设置的 信息
        $dispatcher = $app->getDispatch($route);

        try {
            $action = $dispatcher::initMethodName($routeInfo);
            $namespace = $dispatcher::initMethodNamespace($routeInfo);
            $context = $dispatcher::initMethodContext($request, $response, $namespace, $action);
            $params = $dispatcher::initMethodParams($context, $action, $params);

            static::fire('preDispatch', [$app, $request, $response]);  // 分发之前触发	如果在一个请求处理过程中, 发生了forward 或 callfunc, 则这个事件会被触发多次
            $dispatcher::dispatch($context, $action, $params);  //分发
            static::fire('postDispatch', [$app, $request, $response]);  // 分发结束之后触发	此时动作已经执行结束, 视图也已经渲染完成. 和preDispatch类似, 此事件也可能触发多次

        } catch (Exception $ex) {
            $dispatcher::traceException($request, $response, $ex);
        }
    }


    /**
     * 添加路由到 路由列表 接受请求后 根据添加的先后顺序依次进行匹配 直到成功
     * @param string $route
     * @param RouteInterface $routeObj
     * @param DispatchInterface $dispatch 处理分发接口
     * @return $this
     * @throws AppStartUpError
     */
    public function addRoute($route, RouteInterface $routeObj, DispatchInterface $dispatch = null)
    {
        $route = strtolower($route);
        if ($this->_bootstrap_completed) {
            throw new AppStartUpError('cannot addRoute after bootstrap completed');
        }
        if ($route == $this->_route_name) {
            throw new AppStartUpError("route:{$route} is default route");
        }
        if (isset($this->_routes[$route])) {
            throw new AppStartUpError("route:{$route} has been added");
        }
        $this->_routes[$route] = $routeObj;  //把路由加入路由表
        if (!empty($dispatch)) {   //指定分发器时把分发器加入分发表  未指定时默认使用Application作为分发器
            $this->_dispatches[$route] = $dispatch;
        }
        return $this;
    }

    /**
     * 根据 名字 获取 路由  default 会返回 $this
     * @param string $route
     * @return RouteInterface
     * @throws AppStartUpError
     */
    public function getRoute($route)
    {
        $route = strtolower($route);
        if ($route == $this->_route_name) {
            return $this;
        }
        if (!isset($this->_routes[$route])) {
            {
                throw new AppStartUpError("route:{$route}, routes:" . json_encode(array_keys($this->_routes)) . ' not found');
            }
        }
        return $this->_routes[$route];
    }

    /**
     * 根据 名字 获取 分发器  无匹配则返回 $this
     * @param string $route
     * @return DispatchInterface
     * @throws AppStartUpError
     */
    public function getDispatch($route)
    {
        $route = strtolower($route);
        if (!isset($this->_dispatches[$route])) {
            return $this;
        }
        return $this->_dispatches[$route];
    }

    ###############################################################
    ############ 实现 RouteInterface 默认分发器 ################
    ###############################################################

    /**
     * 根据请求 $request 的 $_method $_request_uri $_language 得出 路由信息 及 参数
     * 匹配成功后 获取 [$routeInfo, $params]  永远不会失败 默认返回 [$this->_routename, [$this->getDefaultRouteInfo(), []]];
     * 一般参数应使用 php 原始 $_GET,$_POST 保存 保持一致性
     * @param Request $request 请求对象
     * @param null $route
     * @return array 匹配成功 [$route, [$routeInfo, $params], ]  失败 ['', [null, null], ]
     * @throws AppStartUpError
     */
    public function route(Request $request, $route = null)
    {
        if (!is_null($route)) {
            return $this->getRoute($route)->route($request);
        }
        foreach ($this->_routes as $route => $val) {
            $tmp = $this->getRoute($route)->route($request);
            if (!empty($tmp[0])) {
                return [$route, $tmp,];
            }
        }
        return [$this->_route_name, [$this->getDefaultRouteInfo(), $request->_request()]];  //无匹配路由时 始终返回自己的默认路由
    }

    /**
     * 根据 路由信息 和 参数 按照路由规则生成 url
     * @param array $routerArr
     * @param array $params
     * @return string
     */
    public function url(array $routerArr, array $params = [])
    {
        return SYSTEM_HOST;
    }

    /**
     * 获取路由 默认参数 用于url参数不齐全时 补全
     * @return array $routeInfo [$controller, $action, $module]
     */
    public function getDefaultRouteInfo()
    {
        return $this->_default_route_info;
    }

    ###############################################################
    ############ 实现 DispatchInterface 默认分发器 ################
    ###############################################################

    /**
     * 根据对象和方法名 获取 修复后的参数
     * @param AbstractContext $object
     * @param $action
     * @param array $params
     * @return array
     */
    public static function initMethodParams(AbstractContext $object, $action, array $params)
    {
        $params = ApiHelper::fixActionParams($object, $action, $params);
        $object->getRequest()->setParams($params);
        return $params;
    }

    /**
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodName(array $routeInfo)
    {
        return $routeInfo[2];
    }

    /**
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodNamespace(array $routeInfo)
    {
        $controller = !empty($routeInfo[1]) ? Func::trimlower($routeInfo[1]) : 'index';
        $module = !empty($routeInfo[0]) ? Func::trimlower($routeInfo[0]) : 'index';

        return "\\" . Func::joinNotEmpty("\\", [Application::app()->getName(), $module, $controller]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $namespace
     * @param string $action
     * @return AbstractController
     * @throws AppStartUpError
     */
    public static function initMethodContext(Request $request, Response $response, $namespace, $action)
    {
        if (!class_exists($namespace)) {
            throw new AppStartUpError("class:{$namespace} not exists with {$namespace}");
        }
        $object = new $namespace($request, $response);
        if (!($object instanceof AbstractController)) {
            throw new AppStartUpError("class:{$namespace} isn't instanceof AbstractController with {$namespace}");
        }
        if (!is_callable([$object, $action])) {
            throw new AppStartUpError("action:{$namespace}::{$action} not callable with {$namespace}");
        }
        $object->setActionName($action);
        $object->beforeAction();  //控制器 beforeAction 不允许显式输出
        return $object;
    }

    /**
     * @param AbstractContext $context
     * @param string $action
     * @param array $params
     */
    public static function dispatch(AbstractContext $context, $action, array $params)
    {
        ob_start();
        call_user_func_array([$context, $action], $params);
        $buffer = ob_get_contents();
        ob_end_clean();

        if (!empty($buffer)) {
            $context->getResponse()->appendBody($buffer);
        }
    }

    /**
     * 处理异常接口 用于捕获分发过程中的异常
     * @param Request $request
     * @param Response $response
     * @param Exception $ex
     */
    public static function traceException(Request $request, Response $response, Exception $ex)
    {
        $code = $ex->getCode();
        $response->setResponseCode(($code >= 500 && $code < 600) ? $code : 500)->appendBody($ex->getMessage());
    }

    ###############################################################
    ############## 重写 EventTrait::isAllowedEvent ################
    ###############################################################

    /**
     *  注册回调函数  回调参数为 callback(Application $app, Request $request, Response $response)
     *  1、routerStartup    在路由之前触发    这个是7个事件中, 最早的一个. 但是一些全局自定的工作, 还是应该放在Bootstrap中去完成
     *  2、routerShutdown    路由结束之后触发    此时路由一定正确完成, 否则这个事件不会触发
     *  3、dispatchLoopStartup    分发循环开始之前被触发
     *  4、preDispatch    分发之前触发    如果在一个请求处理过程中, 发生了forward, 则这个事件会被触发多次
     *  5、postDispatch    分发结束之后触发    此时动作已经执行结束, 视图也已经渲染完成. 和preDispatch类似, 此事件也可能触发多次
     *  6、dispatchLoopShutdown    分发循环结束之后触发    此时表示所有的业务逻辑都已经运行完成, 但是响应还没有发送
     * @param string $event
     * @return bool
     */
    protected static function isAllowedEvent($event)
    {
        static $allow_event = ['routerStartup', 'routerShutdown', 'dispatchLoopStartup', 'preDispatch', 'postDispatch', 'dispatchLoopShutdown',];
        return in_array($event, $allow_event);
    }


    ###############################################################
    ############## 常用 辅助函数 放在这里方便使用 #################
    ###############################################################

    /**
     * 重定向请求到新的路径  HTTP 302 自带 exit 效果
     * @param string $url 要重定向到的URL
     * @return void
     */
    public static function redirect($url)
    {
        header("Location: {$url}");  // Redirect browser
        exit;  // Make sure that code below does not get executed when we redirect.
    }


    /**
     * 加密函数 使用 配置 CRYPT_KEY 作为 key
     * @param string $string 需要加密的字符串
     * @param int $expiry 加密生成的数据 的 有效期 为0表示永久有效， 单位 秒
     * @return string 加密结果 使用了 safe_base64_encode
     */
    public static function encrypt($string, $expiry = 0)
    {
        return Func::encode($string, self::app()->getEnv('CRYPT_KEY', ''), $expiry);
    }

    /**
     * 解密函数 使用 配置 CRYPT_KEY 作为 key  成功返回原字符串  失败或过期 返回 空字符串
     * @param string $string 需解密的 字符串 safe_base64_encode 格式编码
     * @return string 解密结果
     */
    public static function decrypt($string)
    {
        return Func::decode($string, self::app()->getEnv('CRYPT_KEY', ''));
    }

    public static function slotHash($str)
    {
        $crypt_key = static::app()->getEnv('ENV_CRYPT_KEY', '');
        return Func::preMd5($str, $crypt_key);
    }

    public static function encode($string, $expiry = 0)
    {
        $crypt_key = static::app()->getEnv('ENV_CRYPT_KEY', '');
        return Func::encode($string, $crypt_key, $expiry);
    }

    public static function decode($string)
    {
        $crypt_key = static::app()->getEnv('ENV_CRYPT_KEY', '');
        return Func::decode($string, $crypt_key);
    }
}