<?php

namespace Tiny;

use Exception;
use Tiny\Abstracts\AbstractBoot;
use Tiny\Abstracts\AbstractDispatch;
use Tiny\Event\ApplicationEvent;
use Tiny\Exception\AppStartUpError;
use Tiny\Exception\NotFoundError;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;
use Tiny\Interfaces\RouteInterface;


/**
 * Class Application
 * @package Tiny
 */
abstract class Application extends AbstractDispatch implements RouteInterface
{

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
     * @param string $app_name
     * @param array $config
     * @internal param $app_name
     */
    private function __construct(array $config = [], $app_name = 'app')
    {
        $this->_config = $config;
        $this->_app_name = $app_name;
    }

    /**
     * 调试使用 开发模式下有效
     * @param mixed $data
     * @param string|null $tags
     * @param int $ignoreTraceCalls
     */
    public static function _D($data, $tags = null, $ignoreTraceCalls = 0)
    {
        AbstractBoot::consoleDebug($data, $tags, $ignoreTraceCalls);
    }

    ###############################################################
    ############  私有属性 getter setter ################
    ###############################################################

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
    public function setBootstrapCompleted($bootstrap_completed = true)
    {
        $this->_bootstrap_completed = $bootstrap_completed;
    }

    /**
     * @return bool
     */
    public function isBootstrapCompleted()
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

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->_config = $config;
    }

    ###############################################################
    ############ 启动及运行相关函数 ################
    ###############################################################

    /**
     * 运行一个Application, 开始接受并处理请求. 这个方法只能成功调用一次.
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @throws AppStartUpError
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        try {
            if (!$this->_bootstrap_completed) {
                throw new AppStartUpError('call run without bootstrap completed');
            }

            static::fire(new ApplicationEvent('routerStartup', $this, $request, $response));  // 在路由之前触发	这个是7个事件中, 最早的一个. 但是一些全局自定的工作, 还是应该放在Bootstrap中去完成  此时 request 尚未 绑定 response

            $request->bindingResponse($response);

            list($route, list($routeInfo, $params)) = $this->route($request);  // 必定会 匹配到一条路由  默认路由 default=>Application 始终会定向到 index/index->index()  此时 request 已经 绑定 response
            $request->reset_route()->setCurrentRoute($route)->setRouteInfo($routeInfo)->setParams($params)->setRouted();

            static::fire(new ApplicationEvent('routerShutdown', $this, $request, $response));  // 路由结束之后触发	此时路由一定正确完成, 否则这个事件不会触发   此时 response 尚未 绑定 request  输出 还没有准备好

            $response->bindingRequest($request);

            static::fire(new ApplicationEvent('dispatchLoopStartup', $this, $request, $response));  // 分发循环开始之前被触发  response 已经 绑定 request  输出已经准备完毕

            self::forward($request, $response, $routeInfo, $params, $route, false);

            static::fire(new ApplicationEvent('dispatchLoopShutdown', $this, $request, $response));  // 分发循环结束之后触发	此时表示所有的业务逻辑都已经运行完成, 但是响应（有可能）还没有发送  有可能提前结束处理过程 不会调用

            //error_log(date('Y-m-d H:i:s') . " TEST started:" . ($request->isSessionStarted() ? 1 : 0) . ", session_id:" . session_id() . ", status:" . $request->session_status());
            $response->send();

        } catch (Exception $ex) {   // 捕获运行期间的所有异常  由默认异常处理方式进行处理
            static::traceException($request, $response, $ex);
        }
    }

    /**
     * 根据路由信息 dispatch 执行指定 Action 获得缓冲区输出 丢弃函数返回结果  会影响 $request 实例
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $routeInfo 格式为 [$module, $controller, $action] 使用当前相同 设置为空即可
     * @param array|null $params
     * @param string|null $route
     * @param bool $auto_end
     */
    public static function forward(RequestInterface $request, ResponseInterface $response, array $routeInfo = [], array $params = null, $route = null, $auto_end = true)
    {
        $routeInfo = Util::mergeNotEmpty($request->getRouteInfo(), $routeInfo);
        // 对使用默认值 null 的参数 用当前值补全
        if (is_null($route)) {
            $route = $request->getCurrentRoute();
        }
        $app = self::app();
        $app->getRoute($route);  // 检查对应 route 是否注册过
        if (is_null($params)) {
            $params = $request->getParams();
        }

        $request->reset_route()->setCurrentRoute($route)->setRouteInfo($routeInfo)->setRouted();  // 根据新的参数 再次设置 $request 的路由信息  设置完成 锁定 $request

        $response->resetResponse();  // 清空已设置的 信息
        $dispatcher = $app->getDispatch($route);

        try {
            $action = $dispatcher::initMethodName($routeInfo);
            $namespace = $dispatcher::initMethodNamespace($routeInfo);
            $context = $dispatcher::initMethodContext($request, $response, $namespace, $action);
            $params = $dispatcher::initMethodParams($context, $action, $params);

            static::fire(new ApplicationEvent('preDispatch', $app, $request, $response));  // 分发之前触发	如果在一个请求处理过程中, 发生了forward, 则这个事件会被触发多次
            $dispatcher::dispatch($context, $action, $params);  //分发
            static::fire(new ApplicationEvent('postDispatch', $app, $request, $response));  // 分发结束之后触发	此时动作已经执行结束, 视图也已经渲染完成. 和preDispatch类似, 此事件也可能触发多次  也可能 提前结束
            if ($auto_end) {  // 直接结束 处理过程  不再触发  dispatchLoopShutdown
                $response->end();
            }
        } catch (Exception $ex) {   // 捕获运行期间的所有异常  交给 $dispatcher 处理
            $dispatcher::traceException($request, $response, $ex);
        }
    }

    /**
     * 添加路由到 路由列表 接受请求后 根据添加的先后顺序依次进行匹配 直到成功
     * @param string $route
     * @param RouteInterface $router
     * @param AbstractDispatch $dispatcher 处理分发接口
     * @return $this
     * @throws AppStartUpError
     */
    public function addRoute($route, RouteInterface $router, AbstractDispatch $dispatcher = null)
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
     * @return AbstractDispatch
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
     * @param RequestInterface $request 请求对象
     * @param null $route
     * @return array 匹配成功 [$route, [$routeInfo, $params], ]  失败 ['', [null, null], ]
     * @throws NotFoundError
     */
    public function route(RequestInterface $request, $route = null)
    {
        if (!is_null($route)) {
            return $this->getRoute($route)->route($request);
        }
        foreach ($this->_routers as $route => $val) {
            $dispatcher = $this->getDispatch($route);
            try {
                $tmp = $this->getRoute($route)->route($request);
                // 在 route 处理过程中 触发的异常 由对应的 dispatcher 处理
            } catch (NotFoundError $ex) {
                $request->reset_route()->setCurrentRoute($route)->setRouted();
                $response = $request->getBindingResponse();
                static::fire(new ApplicationEvent('routerShutdown', $this, $request, $response));  // 路由结束之后触发	此时路由一定正确完成, 否则这个事件不会触发   此时 response 尚未 绑定 request  输出 还没有准备好
                $response->bindingRequest($request);
                static::fire(new ApplicationEvent('dispatchLoopStartup', $this, $request, $response));  // 分发循环开始之前被触发  response 已经 绑定 request  输出已经准备完毕
                $dispatcher::traceNotFound($request, $request->getBindingResponse(), $ex);
                static::fire(new ApplicationEvent('dispatchLoopShutdown', $this, $request, $response));  // 分发循环结束之后触发	此时表示所有的业务逻辑都已经运行完成, 但是响应（有可能）还没有发送  有可能提前结束处理过程 不会调用
                //error_log(date('Y-m-d H:i:s') . " TEST started:" . ($request->isSessionStarted() ? 1 : 0) . ", session_id:" . session_id() . ", status:" . $request->session_status());
                $response->end();  // 异常处理完毕   不再继续
            } catch (Exception $ex) {
                $request->reset_route()->setCurrentRoute($route)->setRouted();
                $response = $request->getBindingResponse();
                static::fire(new ApplicationEvent('routerShutdown', $this, $request, $response));  // 路由结束之后触发	此时路由一定正确完成, 否则这个事件不会触发   此时 response 尚未 绑定 request  输出 还没有准备好
                $response->bindingRequest($request);
                static::fire(new ApplicationEvent('dispatchLoopStartup', $this, $request, $response));  // 分发循环开始之前被触发  response 已经 绑定 request  输出已经准备完毕
                $dispatcher::traceException($request, $request->getBindingResponse(), $ex);
                static::fire(new ApplicationEvent('dispatchLoopShutdown', $this, $request, $response));  // 分发循环结束之后触发	此时表示所有的业务逻辑都已经运行完成, 但是响应（有可能）还没有发送  有可能提前结束处理过程 不会调用
                //error_log(date('Y-m-d H:i:s') . " TEST started:" . ($request->isSessionStarted() ? 1 : 0) . ", session_id:" . session_id() . ", status:" . $request->session_status());
                $response->end();  // 异常处理完毕   不再继续
            }
            if (!empty($tmp[0])) {
                return [$route, $tmp,];
            }
        }
        $uri = strtotime($request->fixRequestPath());
        if ($uri == '/' || $uri == '/index/' || $uri == '/index/index/index/') {
            return [$this->_route_name, [$this->defaultRoute(), $request->all_request()]];  //无匹配路由时 始终返回自己的默认路由
        }
        throw new NotFoundError('page not found');
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

    /**
     * 获取路由 默认参数 用于url参数不齐全时 补全
     * @return array $routeInfo [$controller, $action, $module]
     */
    public function defaultRoute()
    {
        return $this->_default_route_info;
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
     * @param string|null $appname
     * @param array|null $config
     * @return Application
     */
    public static function app($appname = null, array $config = null)
    {
        if (is_null(self::$_instance)) {
            $_config = !is_null($config) ? $config : [];
            self::$_instance = new static($_config, $appname);
        }
        return self::$_instance;
    }

    public static function appname()
    {
        return static::app()->getAppName();
    }

    /**
     * @return string
     */
    public static function environ()
    {
        return trim(self::config('ENVIRON', 'product'));
    }

    public static function dev()
    {
        return Util::stri_cmp('debug', self::environ());
    }

    /**
     * 加密函数 使用 配置 CRYPT_KEY 作为 key
     * @param string $string 需要加密的字符串
     * @param int $expiry 加密生成的数据 的 有效期 为0表示永久有效， 单位 秒
     * @param string $salt
     * @return string 加密结果 使用了 safe_base64_encode
     */
    public static function encrypt($string, $expiry = 0, $salt = '')
    {
        return Util::encode($string, self::config('CRYPT_KEY', ''), $expiry, $salt);
    }

    /**
     * 解密函数 使用 配置 CRYPT_KEY 作为 key  成功返回原字符串  失败或过期 返回 空字符串
     * @param string $string 需解密的 字符串 safe_base64_encode 格式编码
     * @param string $salt
     * @return string 解密结果
     */
    public static function decrypt($string, $salt = '')
    {
        return Util::decode($string, self::config('CRYPT_KEY', ''), $salt);
    }

    /**
     * 获取 全局配置 指定key的值 不存在则返回 default
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function config($key, $default = '')
    {
        $config = static::app()->getConfig();
        return self::_find_config($key, $default, $config);
    }

    public static function set_config($key, $val)
    {
        if (strpos($key, '.') !== false) {
            throw new AppStartUpError("cannot set config with key with dot, key: {$key}");
        }
        $config = static::app()->getConfig();
        $last_val = !empty($config[$key]) ? $config[$key] : '';
        if (is_array($last_val) && is_array($val)) {
            $config[$key] = Util::deep_merge($last_val, $val);
        } else {
            $config[$key] = $val;
        }
        static::app()->setConfig($config);
    }

    private static function _find_config($key, $default = '', array $config = [])
    {
        $tmp_list = explode('.', $key, 2);
        $pre_key = !empty($tmp_list[0]) ? trim($tmp_list[0]) : '';
        $last_key = !empty($tmp_list[1]) ? trim($tmp_list[1]) : '';
        if (!empty($pre_key)) {
            if (empty($last_key)) {
                return isset($config[$pre_key]) ? $config[$pre_key] : $default;
            }
            $config = isset($config[$pre_key]) ? $config[$pre_key] : [];
            if (!is_array($config)) {
                throw  new AppStartUpError("get config key:{$key} but value at {$pre_key} not array");
            }
        }
        return self::_find_config($last_key, $default, $config);
    }

    ###############################################################
    ############## 常用 静态函数 放在这里方便使用 #################
    ###############################################################

    public static function path(array $paths = [], $add_last = true, $seq = DIRECTORY_SEPARATOR)
    {
        // if not set config ROOT_PATH, try find root by "root\vendor\wowngasb\tiny\src\"
        $path = self::config('ROOT_PATH', '');
        $path = !empty($path) ? $path : dirname(dirname(dirname(dirname(__DIR__))));
        while (Util::str_endwith($path, $seq)) {
            $path = substr($path, 0, -strlen($seq));
        }
        $add_path = Util::joinNotEmpty($seq, $paths);
        while (Util::str_endwith($add_path, $seq)) {
            $add_path = substr($add_path, 0, -strlen($seq));
        }
        $last_seq = $add_last ? $seq : '';
        return empty($add_path) ? "{$path}{$last_seq}" : "{$path}{$seq}{$add_path}{$last_seq}";
    }

    public static function cache_path(array $paths = [], $add_last = true, $seq = DIRECTORY_SEPARATOR)
    {
        // if not set config CACHE_PATH, try find cache in root/cache"
        $path = self::config('CACHE_PATH', '');
        if (empty($path)) {
            $path = static::path(['cache'], true, $seq);
        }
        while (Util::str_endwith($path, $seq)) {
            $path = substr($path, 0, -strlen($seq));
        }
        $add_path = Util::joinNotEmpty($seq, $paths);
        while (Util::str_endwith($add_path, $seq)) {
            $add_path = substr($add_path, 0, -strlen($seq));
        }
        $last_seq = $add_last ? $seq : '';
        return empty($add_path) ? "{$path}{$last_seq}" : "{$path}{$seq}{$add_path}{$last_seq}";
    }

    /**
     * 根据 路由信息 和 参数 按照路由规则生成 url
     * @param RequestInterface $request
     * @param array $routeInfo 格式为 [$module, $controller, $action] 使用当前相同 设置为空即可
     * @param array $params
     * @return string
     * @throws AppStartUpError
     */
    public static function url(RequestInterface $request, array $routeInfo = [], array $params = [])
    {
        $route = $request->getCurrentRoute();
        $routeInfo = Util::mergeNotEmpty($request->getRouteInfo(), $routeInfo);
        return Application::app()->getRoute($route)->buildUrl($request->schema(), $request->host(), $routeInfo, $params);
    }

    /**
     * 重定向请求到新的路径  HTTP 302
     * @param ResponseInterface $response
     * @param string $url 要重定向到的URL
     * @return void
     */
    public static function redirect(ResponseInterface $response, $url)
    {
        $response->resetResponse()->addHeader("Location: {$url}")->setResponseCode(302)->end();
    }

}