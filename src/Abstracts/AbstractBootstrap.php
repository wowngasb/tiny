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
use Tiny\OrmQuery\OrmConfig;
use Tiny\Request;
use Tiny\Response;

abstract class AbstractBootstrap
{

    /** 在app run 之前, 设置app 命名空间 并 注册路由
     *  #param Application $app
     *  #return Application
     * @param string $appname
     * @param Application $app
     * @return Application
     */
    public static function bootstrap($appname, Application $app)
    {
        $app->setAppName($appname);  // 添加默认简单路由

        self::debugStrap();
        $app->setBootstrapCompleted();
        return $app;
    }

    public static function debugConsole($data, $tags = null, $ignoreTraceCalls = 0)
    {
        if (defined('DEV_MODEL') && DEV_MODEL == 'DEBUG') {
            if (!empty($tags)) {
                $tags = strval($tags) . ':' . Application::usedMilliSecond() . 'ms';
            }
            Connector::getInstance()->getDebugDispatcher()->dispatchDebug($data, $tags, $ignoreTraceCalls);
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

    public static function debugStrap()
    {
        if (defined('DEV_MODEL') && DEV_MODEL != 'DEBUG') {  // 非调试模式下  直接返回
            return;
        }

        //开启 辅助调试模式 注册对应事件
        Connector::getInstance()->setPassword(Application::app()->getEnv('ENV_DEVELOP_KEY'), true);

        Application::on('routerStartup', function (Application $obj, Request $request, Response $response) {
            false && func_get_args();
            $data = ['_request' => $request, 'request' => $request->_request()];
            self::debugConsole($data, get_class($obj) . ' #routerStartup', 1);
        });
        /* Application::on('routerShutdown', function (Application $obj, Request $request, Response $response) {
            false && func_get_args();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->strRouteInfo(), 'request' => $request->_request()];
            self::debugConsole($data, get_class($obj) . ' #routerShutdown', 1);
        }); */
        Application::on('dispatchLoopStartup', function (Application $obj, Request $request, Response $response) {
            false && func_get_args();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->strRouteInfo(), 'request' => $request->_request()];
            if ($request->isSessionStarted()) {
                $data['session'] = $request->_session();
            }
            self::debugConsole($data, get_class($obj) . ' #dispatchLoopStartup', 1);
        });
        /*
        Application::on('dispatchLoopShutdown', function (Application $obj, Request $request, Response $response) {
            false && func_get_args();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->strRouteInfo(), 'body' => $response->getBody()];
            self::debugConsole($data, get_class($obj) . ' #dispatchLoopShutdown', 1);
        }); */
        Application::on('preDispatch', function (Application $obj, Request $request, Response $response) {
            false && func_get_args();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->strRouteInfo(), 'params' => $request->getParams(), 'request' => $request->_request(), 'session' => $request->_session(), 'cookie' => $request->_cookie()];
            self::debugConsole($data, get_class($obj) . ' #preDispatch', 1);
        });
        /*
        Application::on('postDispatch', function (Application $obj, Request $request, Response $response) {
            false && func_get_args();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->strRouteInfo()];
            self::debugConsole($data, get_class($obj) . ' #postDispatch', 1);
        }); */

        AbstractController::on('preDisplay', function (AbstractController $obj, $tpl_path, array $params) {
            false && func_get_args();
            $data = ['params' => $params, 'tpl_path' => $tpl_path];
            self::debugConsole($data, get_class($obj) . ' #preDisplay', 1);
        });  // 注册 模版渲染 打印模版变量  用于调试
        AbstractController::on('preWidget', function (AbstractController $obj, $tpl_path, array $params) {
            false && func_get_args();
            $data = ['params' => $params, 'tpl_path' => $tpl_path];
            self::debugConsole($data, get_class($obj) . ' #preWidget', 1);
        });  // 注册 组件渲染 打印组件变量  用于调试

        OrmConfig::on('runSql', function (OrmConfig $obj, $sql_str, $time, $_tag) {
            false && func_get_args();
            if ($obj->debug) {
                $use_str = round($time * 1000, 2) . 'ms';
                AbstractBootstrap::debugConsole($sql_str, ' >>> ' . $_tag . " ({$use_str})");
            }
        });  // 注册 SQl执行 打印相关信息  用于调试

        AbstractApi::on('apiResult', function ($obj, $action, $params, $result, $callback) {
            AbstractBootstrap::debugConsole([
                'method' => $action,
                'params' => $params,
                'result' => $result,
                'callback' => $callback,
            ], get_class($obj) . ' #api');
        });

        AbstractApi::on('apiException', function ($obj, $action, $params, $ex, $callback) {
            AbstractBootstrap::debugConsole([
                'method' => $action,
                'params' => $params,
                'exception' => $ex,
                'callback' => $callback,
            ], get_class($obj) . ' #exception');
        });
    }

}