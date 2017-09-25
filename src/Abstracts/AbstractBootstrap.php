<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/9 0009
 * Time: 17:30
 */

namespace Tiny\Abstracts;


use PhpConsole\Connector;
use Tiny\Application;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;

abstract class AbstractBootstrap
{

    /** 在app run 之前, 设置app 并注册路由
     *  #param Application $app
     *  #return Application
     * @param Application $app
     * @return Application
     */
    public static function bootstrap(Application $app)
    {
        if (Application::dev()) {
            self::debugStrap();
        }
        $app->setBootstrapCompleted(true);
        return $app;
    }

    public static function debugConsole($data, $tag = null, $ignoreTraceCalls = 0)
    {
        if (Application::dev()) {
            Connector::getInstance()->getDebugDispatcher()->dispatchDebug($data, $tag, $ignoreTraceCalls);
        }
    }

    /**
     * 调试使用 开发模式下有效
     * @param array $data
     * @param string|null $tags
     * @param int $ignoreTraceCalls
     */
    public static function _D($data, $tags = null, $ignoreTraceCalls = 0)
    {
        self::debugConsole($data, $tags, $ignoreTraceCalls);
    }

    private static function debugStrap()
    {
        if (!Application::dev()) {  // 非调试模式下  直接返回
            return;
        }

        //开启 辅助调试模式 注册对应事件
        Connector::getInstance()->setPassword(Application::config('ENV_DEVELOP_KEY'), true);

        Application::on('routerStartup', function (Application $obj, RequestInterface $request, ResponseInterface $response) {
            false && func_get_args();
            $data = ['_request' => $request, 'request' => $request->all_request()];
            $tag = $request->debugTag(get_class($obj) . ' #routerStartup');
            self::debugConsole($data, $tag, 1);
        });
        /* Application::on('routerShutdown', function (Application $obj, Request $request, Response $response) {
            false && func_get_args();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->strRouteInfo(), 'request' => $request->all_request()];
            $tag = $request->debugTag(get_class($obj) . ' #routerShutdown');
            self::debugConsole($data, $tag, 1);
        }); */
        Application::on('dispatchLoopStartup', function (Application $obj, RequestInterface $request, ResponseInterface $response) {
            false && func_get_args();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri(), 'request' => $request->all_request()];
            if ($request->isSessionStarted()) {
                $data['session'] = $request->all_session();
            }
            $tag = $request->debugTag(get_class($obj) . ' #dispatchLoopStartup');
            self::debugConsole($data, $tag, 1);
        });
        /*
        Application::on('dispatchLoopShutdown', function (Application $obj, Request $request, Response $response) {
            false && func_get_args();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->strRouteInfo(), 'body' => $response->getBody()];
            $tag = $request->debugTag(get_class($obj) . ' #dispatchLoopShutdown');
            self::debugConsole($data, $tag, 1);
        }); */
        Application::on('preDispatch', function (Application $obj, RequestInterface $request, ResponseInterface $response) {
            false && func_get_args();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri(), 'params' => $request->getParams(), 'request' => $request->all_request(), 'session' => $request->all_session(), 'cookie' => $request->all_cookie()];
            $tag = $request->debugTag(get_class($obj) . ' #preDispatch');
            self::debugConsole($data, $tag, 1);
        });
        /*
        Application::on('postDispatch', function (Application $obj, Request $request, Response $response) {
            false && func_get_args();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->strRouteInfo()];
            $tag = $request->debugTag(get_class($obj) . ' #postDispatch');
            self::debugConsole($data, $tag, 1);
        }); */

        AbstractController::on('preDisplay', function (AbstractController $obj, $tpl_path, array $params) {
            false && func_get_args();
            $data = ['params' => $params, 'tpl_path' => $tpl_path];
            $tag = $obj->getRequest()->debugTag(get_class($obj) . ' #preDisplay');
            self::debugConsole($data, $tag, 1);
        });  // 注册 模版渲染 打印模版变量  用于调试
        AbstractController::on('preWidget', function (AbstractController $obj, $tpl_path, array $params) {
            false && func_get_args();
            $data = ['params' => $params, 'tpl_path' => $tpl_path];
            $tag = $obj->getRequest()->debugTag(get_class($obj) . ' #preWidget');
            self::debugConsole($data, $tag, 1);
        });  // 注册 组件渲染 打印组件变量  用于调试


        /*
        OrmConfig::on('runSql', function (OrmConfig $obj, $sql_str, $time, $_tag) {
            false && func_get_args();
            $time_str = round($time, 3) * 1000;
            static::debugConsole("{$sql_str} <{$time_str}ms>", $_tag, 1);
        });  // 注册 SQl执行 打印相关信息  用于调试
        */

        AbstractApi::on('apiResult', function (AbstractApi $obj, $action, $params, $result, $callback) {
            $tag = $obj->getRequest()->debugTag(get_class($obj) . ' #api');
            AbstractBootstrap::debugConsole([
                'method' => $action,
                'params' => $params,
                'result' => $result,
                'callback' => $callback,
            ], $tag);
        });

        AbstractApi::on('apiException', function (AbstractApi $obj, $action, $params, $ex, $callback) {
            $tag = $obj->getRequest()->debugTag(get_class($obj) . ' #api');
            AbstractBootstrap::debugConsole([
                'method' => $action,
                'params' => $params,
                'exception' => $ex,
                'callback' => $callback,
            ], $tag);
        });
    }

}