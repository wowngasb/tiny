<?php

namespace Tiny;

use Exception;

use Tiny\Abstracts\AbstractContext;
use Tiny\Abstracts\AbstractController;

use Tiny\Exception\AppStartUpError;
use Tiny\Interfaces\DispatchInterface;
use Tiny\Interfaces\RouteInterface;

use Tiny\Plugin\ApiHelper;
use Tiny\Traits\EventTrait;

/**
 * Class Application
 * @package Tiny
 */
final class Application implements DispatchInterface, RouteInterface
{
    use EventTrait;

    // 配置相关
    private $_config = [];  // 全局配置
    private $_app_name = 'app';  // app 目录，用于 拼接命名空间 和 定位模板文件
    private $_bootstrap_completed = false;  // 布尔值, 指明当前的Application是否已经运行

    // 已添加的 路由器 和 分发器 列表
    private $_routers = [];  // 路由列表
    private $_dispatchers = [];  // 分发列表

    // 实现 RouteInterface 接口 Application 本身就是一个 default 路由 总是会返回 ['index', 'index', 'index']
    private $_route_name = 'default';  // 默认路由名字，总是会路由到 index
    private $_default_route_info = ['index', 'index', 'index'];

    // 单实例 实现
    private static $_instance = null;  // Application实现单利模式, 此属性保存当前实例

    /**
     * Application constructor.
     * @param array $config 关联数组的配置
     */
    private function __construct(array $config = null)
    {
        $this->_config = !is_null($config) ? $config : $this->_config;
    }

    ###############################################################
    ############  私有属性 getter setter ################
    ###############################################################

    /**
     * @param string $appname
     * @throws AppStartUpError
     */
    public function setAppName($appname)
    {
        if ($this->_bootstrap_completed) {
            throw new AppStartUpError('call setAppName but bootstrap completed');
        }
        $this->_app_name = $appname;
    }


    /**
     * @param void
     * @return string
     */
    public function getAppName()
    {
        return $this->_app_name;
    }

    /**
     * @param bool $bootstrap_completed
     */
    public function setBootstrapCompleted($bootstrap_completed)
    {
        $this->_bootstrap_completed = $bootstrap_completed;
    }

    /**
     * @return bool
     */
    public function getBootstrapCompleted()
    {
        return $this->_bootstrap_completed;
    }

    /**
     * @param void
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    ###############################################################
    ############ 启动及运行相关函数 ################
    ###############################################################

    /**
     * 运行一个Application, 开始接受并处理请求. 这个方法只能成功调用一次.
     * @param Request $request
     * @param Response $response
     * @throws AppStartUpError
     */
    public function run(Request $request, Response $response)
    {
        if (!$this->_bootstrap_completed) {
            throw new AppStartUpError('call run without bootstrap completed');
        }
        static::fire('routerStartup', [$this, $request, $response]);  // 在路由之前触发	这个是7个事件中, 最早的一个. 但是一些全局自定的工作, 还是应该放在Bootstrap中去完成

        list($route, list($routeInfo, $params)) = $this->route($request);  // 必定会 匹配到一条路由  默认路由 default=>Application 始终会定向到 index/index->index()

        $request->reset_route()
            ->setCurrentRoute($route)
            ->setRouteInfo($routeInfo)
            ->setParams($params)
            ->setIsRouted();

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

        $request->reset_route()
            ->setCurrentRoute($route)
            ->setRouteInfo($routeInfo)
            ->setIsRouted();  // 根据新的参数 再次设置 $request 的路由信息
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
     * @param RouteInterface $router
     * @param DispatchInterface $dispatcher 处理分发接口
     * @return $this
     * @throws AppStartUpError
     */
    public function addRoute($route, RouteInterface $router, DispatchInterface $dispatcher = null)
    {
        $route = strtolower($route);
        if ($this->_bootstrap_completed) {
            throw new AppStartUpError('cannot addRoute after bootstrap completed');
        }
        if ($route == $this->_route_name) {
            throw new AppStartUpError("route:{$route} is default route");
        }
        if (isset($this->_routers[$route])) {
            throw new AppStartUpError("route:{$route} has been added");
        }
        $this->_routers[$route] = $router;  //把路由加入路由表
        if (!empty($dispatcher)) {   //指定分发器时把分发器加入分发表  未指定时默认使用Application作为分发器
            $this->_dispatchers[$route] = $dispatcher;
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
        if (!isset($this->_routers[$route])) {
            {
                throw new AppStartUpError("route:{$route}, routes:" . json_encode(array_keys($this->_routers)) . ' not found');
            }
        }
        return $this->_routers[$route];
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
        if (!isset($this->_dispatchers[$route])) {
            return $this;
        }
        return $this->_dispatchers[$route];
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
        foreach ($this->_routers as $route => $val) {
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
        return self::host();
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
        $appname = Application::app()->getAppName();
        return "\\" . Func::joinNotEmpty("\\", [$appname, $module, $controller]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param string $namespace
     * @param string $action
     * @return AbstractContext
     * @throws AppStartUpError
     */
    public static function initMethodContext(Request $request, Response $response, $namespace, $action)
    {
        if (!class_exists($namespace)) {
            throw new AppStartUpError("class:{$namespace} not exists with {$namespace}");
        }
        $context = new $namespace($request, $response);
        if (!($context instanceof AbstractController)) {
            throw new AppStartUpError("class:{$namespace} isn't instanceof AbstractController with {$namespace}");
        }
        if (!is_callable([$context, $action])) {
            throw new AppStartUpError("action:{$namespace}::{$action} not callable with {$namespace}");
        }
        $context->setActionName($action);
        return $context;
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
        $string_buffer = ob_get_contents();
        ob_end_clean();

        if (!empty($string_buffer)) {
            $context->getResponse()->appendBody($string_buffer);
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
        $http_code = $code >= 500 && $code < 600 ? $code : 500;
        $response->clearBody()->setResponseCode($http_code)->appendBody($ex->getMessage());
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
     * 获取当前的Application实例
     * @param array|null $config
     * @return Application
     */
    public static function app(array $config = null)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($config);
        }
        return self::$_instance;
    }

    public static function path_join(array $paths = [], $seq = DIRECTORY_SEPARATOR)
    {
        // if not define ROOT_PATH, try find root by "root\vendor\wowngasb\tiny\src\"
        $root_path = defined('ROOT_PATH') ? ROOT_PATH : dirname(dirname(dirname(dirname(__DIR__))));
        $abs_path = Func::joinNotEmpty(DIRECTORY_SEPARATOR, $paths);
        return empty($abs_path) ? "{$root_path}{$seq}" : "{$root_path}{$seq}{$abs_path}{$seq}";
    }

    public static function environ()
    {
        return trim(self::get_config('ENVIRON', 'product'));
    }

    public static function is_dev()
    {
        return Func::stri_cmp('debug', self::environ());
    }

    public static function host()
    {
        return defined('SYSTEM_HOST') ? SYSTEM_HOST : 'http://localhost/';
    }

    /**
     * 获取 全局配置 指定key的值 不存在则返回 default
     * @param string $key
     * @param mixed $default
     * @param array|null $config 配置数组 如果为 null 则使用 app()->getConfig()
     * @return mixed
     * @throws AppStartUpError
     */
    public static function get_config($key, $default = '', array $config = null)
    {
        $config = is_null($config) ? static::app()->getConfig() : $config;
        $tmp_list = explode('.', $key, 2);
        $pre_key = trim($tmp_list[0]);
        $last_key = trim($tmp_list[1]);
        if (!empty($pre_key)) {
            if (empty($last_key)) {
                return isset($config[$pre_key]) ? $config[$pre_key] : $default;
            }
            $config = isset($config[$pre_key]) ? $config[$pre_key] : [];
            if (!is_array($config)) {
                throw  new AppStartUpError("get config key:{$key} but value at {$pre_key} not array");
            }
        }
        return self::get_config($last_key, $default, $config);
    }

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
        return Func::encode($string, self::get_config('CRYPT_KEY'), $expiry);
    }

    /**
     * 解密函数 使用 配置 CRYPT_KEY 作为 key  成功返回原字符串  失败或过期 返回 空字符串
     * @param string $string 需解密的 字符串 safe_base64_encode 格式编码
     * @return string 解密结果
     */
    public static function decrypt($string)
    {
        return Func::decode($string, self::get_config('CRYPT_KEY'));
    }

}