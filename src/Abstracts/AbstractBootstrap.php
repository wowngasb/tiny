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
use Tiny\Event\ApiEvent;
use Tiny\Event\ApplicationEvent;
use Tiny\Event\ControllerEvent;
use Tiny\Event\OrmEvent;
use Tiny\OrmQuery\OrmContext;

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

    public static function consoleDebug($data, $tag = null, $ignoreTraceCalls = 0)
    {
        if (Application::dev()) {
            Connector::getInstance()->getDebugDispatcher()->dispatchDebug($data, $tag, $ignoreTraceCalls);
        }
    }

    public static function consoleException($exception)
    {
        if (Application::dev()) {
            Connector::getInstance()->getErrorsDispatcher()->dispatchException($exception);
        }
    }

    public static function consoleError($code = null, $text = null, $file = null, $line = null, $ignoreTraceCalls = 0)
    {
        if (Application::dev()) {
            Connector::getInstance()->getErrorsDispatcher()->dispatchError($code, $text, $file, $line, $ignoreTraceCalls);
        }
    }

    private static function debugStrap()
    {
        if (!Application::dev()) {  // 非调试模式下  直接返回
            return;
        }

        //开启 辅助调试模式 注册对应事件
        Connector::getInstance()->setPassword(Application::config('ENV_DEVELOP_KEY'), true);

        Application::on('routerStartup', function (ApplicationEvent $event) {
            list($obj, $request) = [$event->getObject(), $event->getRequest()];
            $data = ['_request' => $request, 'request' => $request->all_request()];
            $tag = $request->debugTag(get_class($obj) . ' #routerStartup');
            static::consoleDebug($data, $tag, 1);
        });

        Application::on('routerShutdown', function (ApplicationEvent $event) {
            list($obj, $request) = [$event->getObject(), $event->getRequest()];
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri(), 'request' => $request->all_request()];
            $tag = $request->debugTag(get_class($obj) . ' #routerStartup');
            static::consoleDebug($data, $tag, 1);
        });
        Application::on('dispatchLoopStartup', function (ApplicationEvent $event) {
            list($obj, $request) = [$event->getObject(), $event->getRequest()];
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri(), 'request' => $request->all_request()];
            if ($request->isSessionStarted()) {
                $data['session'] = $request->all_session();
            }
            $tag = $request->debugTag(get_class($obj) . ' #dispatchLoopStartup');
            static::consoleDebug($data, $tag, 1);
        });

        Application::on('dispatchLoopShutdown', function (ApplicationEvent $event) {
            list($obj, $request, $response) = [$event->getObject(), $event->getRequest(), $event->getResponse()];
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri(), 'body' => $response->getBody()];
            $tag = $request->debugTag(get_class($obj) . ' #dispatchLoopShutdown');
            static::consoleDebug($data, $tag, 1);
        });
        Application::on('preDispatch', function (ApplicationEvent $event) {
            list($obj, $request) = [$event->getObject(), $event->getRequest()];
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri(), 'params' => $request->getParams(), 'request' => $request->all_request(), 'session' => $request->all_session(), 'cookie' => $request->all_cookie()];
            $tag = $request->debugTag(get_class($obj) . ' #preDispatch');
            static::consoleDebug($data, $tag, 1);
        });

        Application::on('postDispatch', function (ApplicationEvent $event) {
            list($obj, $request) = [$event->getObject(), $event->getRequest()];
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri()];
            $tag = $request->debugTag(get_class($obj) . ' #postDispatch');
            static::consoleDebug($data, $tag, 1);
        });

        AbstractController::on('preDisplay', function (ControllerEvent $event) {
            list($obj, $params, $tpl_path) = [$event->getObject(), $event->getViewArgs(), $event->getViewFile()];
            $layout = $obj->getLayout();
            $file_name = pathinfo($tpl_path, PATHINFO_FILENAME);
            unset($params['action_content']);
            $data = ['params' => $params, 'tpl_path' => $tpl_path];
            $tag = $obj->getRequest()->debugTag(get_class($obj) . ' #preDisplay' . (!empty($layout) ? "[{$file_name} #{$layout}]" : ''));
            static::consoleDebug($data, $tag, 1);
        });  // 注册 模版渲染 打印模版变量  用于调试
        AbstractController::on('preWidget', function (ControllerEvent $event) {
            list($obj, $params, $tpl_path) = [$event->getObject(), $event->getViewArgs(), $event->getViewFile()];

            $file_name = pathinfo($tpl_path, PATHINFO_FILENAME);
            $data = ['params' => $params, 'tpl_path' => $tpl_path];
            $tag = $obj->getRequest()->debugTag(get_class($obj) . " #preWidget [{$file_name}]");
            static::consoleDebug($data, $tag, 1);
        });  // 注册 组件渲染 打印组件变量  用于调试

        AbstractApi::on('apiResult', function (ApiEvent $event) {
            $obj = $event->getObject();
            $tag = $obj->getRequest()->debugTag(get_class($obj) . ' #apiResult');
            static::consoleDebug([
                'method' => $event->getAction(),
                'params' => $event->getParams(),
                'result' => $event->getResult(),
                'callback' => $event->getCallback(),
            ], $tag);
        });

        AbstractApi::on('apiException', function (ApiEvent $event) {
            $obj = $event->getObject();
            $tag = $obj->getRequest()->debugTag(get_class($obj) . ' #apiException');
            static::consoleDebug([
                'method' => $event->getAction(),
                'params' => $event->getParams(),
                'exception' => $event->getException(),
                'callback' => $event->getCallback(),
            ], $tag);
            static::consoleException($event->getException());
        });

        OrmContext::on('runSql', function (OrmEvent $event) {
            list($sql_str, $args, $time, $_tag) = [$event->getSql(), $event->getArgs(), $event->getTime(), $event->getTag()];
            $time_str = round($time, 3) * 1000;
            static::consoleDebug([$sql_str, $args], "[SQL] {$_tag} <{$time_str}ms>", 1);
        });
    }

}