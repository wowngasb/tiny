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
use Tiny\Traits\OrmConfig;
use Tiny\Util;

abstract class AbstractBoot
{

    /** 在app run 之前, 设置app 并注册路由
     *  #param Application $app
     *  #return Application
     * @param Application $app
     * @return Application
     */
    public static function bootstrap(Application $app)
    {
        $app->setBootstrapCompleted(true);
        return $app;
    }

    /** @var Connector $ex */
    protected static $_consoleInstance = null;

    /**
     * @return Connector
     */
    protected static function getConsoleInstance()
    {
        //开启 辅助调试模式 注册对应事件
        if (empty(static::$_consoleInstance)) {
            static::$_consoleInstance = Connector::getInstance();
        }
        return static::$_consoleInstance;
    }

    public static function consoleDebug($data, $tag = null, $ignoreTraceCalls = 0)
    {
        if (Application::dev()) {
            static::getConsoleInstance()->getDebugDispatcher()->dispatchDebug($data, $tag, $ignoreTraceCalls);
        }
    }

    public static function consoleException($exception)
    {
        if (Application::dev()) {
            static::getConsoleInstance()->getErrorsDispatcher()->dispatchException($exception);
        }
    }

    public static function consoleError($code = null, $text = null, $file = null, $line = null, $ignoreTraceCalls = 0)
    {
        if (Application::dev()) {
            static::getConsoleInstance()->getErrorsDispatcher()->dispatchError($code, $text, $file, $line, $ignoreTraceCalls);
        }
    }

    protected static function actionRouterStartup(ApplicationEvent $event)
    {
        $obj = $event->getObject();
        $request = $event->getRequest();
        $data = ['_request' => $request->getRequestUri(), 'request' => $request->all_request()];
        $tag = $request->debugTag(get_class($obj) . ' #routerStartup');
        static::consoleDebug($data, $tag, 1);
    }

    protected static function debugStrap($routerShutdown = false, $dispatchLoopStartup = false, $dispatchLoopShutdown = false, $preDispatch = false, $postDispatch = false, $preDisplay = false, $preWidget = false, $apiResult = false, $apiException = false, $runSql = false)
    {
        if (!Application::dev()) {  // 非调试模式下  直接返回
            return;
        }

        $routerShutdown && Application::on('routerShutdown', function (ApplicationEvent $event) {
            $obj = $event->getObject();
            $request = $event->getRequest();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri(), 'request' => $request->all_request()];
            $tag = $request->debugTag(get_class($obj) . ' #routerShutdown');
            static::consoleDebug($data, $tag, 1);
        });

        $dispatchLoopStartup && Application::on('dispatchLoopStartup', function (ApplicationEvent $event) {
            $obj = $event->getObject();
            $request = $event->getRequest();
            $all_session = $request->all_session();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri(), 'request' => $request->all_request()];
            if ($request->isSessionStarted()) {
                $data['session'] = $all_session;
                $data['session_status'] = $request->session_status();
            }
            $tag = $request->debugTag(get_class($obj) . ' #dispatchLoopStartup');
            static::consoleDebug($data, $tag, 1);
        });

        $dispatchLoopShutdown && Application::on('dispatchLoopShutdown', function (ApplicationEvent $event) {
            $obj = $event->getObject();
            $request = $event->getRequest();
            $response = $event->getResponse();
            $_body = $response->getBody();
            $body = '';
            foreach ($_body as $html) {
                $body .= $html;
            }
            $total = strlen($body);
            $body_msg = $total > 500 ? substr($body, 0, 500) . "...total<{$total}>chars..." : $body;
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri(), 'body' => $body_msg];
            $tag = $request->debugTag(get_class($obj) . ' #dispatchLoopShutdown');
            static::consoleDebug($data, $tag, 1);
        });

        $preDispatch && Application::on('preDispatch', function (ApplicationEvent $event) {
            $obj = $event->getObject();
            $request = $event->getRequest();
            $all_session = $request->all_session();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri(), 'params' => $request->getParams(), 'request' => $request->all_request(), 'session' => $all_session, 'cookie' => $request->all_cookie()];
            $tag = $request->debugTag(get_class($obj) . ' #preDispatch');
            static::consoleDebug($data, $tag, 1);
        });

        $postDispatch && Application::on('postDispatch', function (ApplicationEvent $event) {
            $obj = $event->getObject();
            $request = $event->getRequest();
            $data = ['route' => $request->getCurrentRoute(), 'routeInfo' => $request->getRouteInfoAsUri()];
            $tag = $request->debugTag(get_class($obj) . ' #postDispatch');
            static::consoleDebug($data, $tag, 1);
        });

        $preDisplay && AbstractController::on('preDisplay', function (ControllerEvent $event) {
            $obj = $event->getObject();
            $params = $event->getViewArgs();
            $tpl_path = $event->getViewFile();
            $layout = $obj->_getLayout();
            $file_name = pathinfo($tpl_path, PATHINFO_FILENAME);
            unset($params['action_content']);
            $data = self::tryFixParams($params);
            $data['__tpl_path__'] = $tpl_path;
            $tag = $obj->getRequest()->debugTag(get_class($obj) . ' #preDisplay' . (!empty($layout) ? "[{$file_name} #{$layout}]" : ''));
            static::consoleDebug($data, $tag, 1);
        });  // 注册 模版渲染 打印模版变量  用于调试

        $preWidget && AbstractController::on('preWidget', function (ControllerEvent $event) {
            $obj = $event->getObject();
            $params = $event->getViewArgs();
            $tpl_path = $event->getViewFile();
            $file_name = pathinfo($tpl_path, PATHINFO_FILENAME);
            $data = self::tryFixParams($params);
            $data['__tpl_path__'] = $tpl_path;
            $tag = $obj->getRequest()->debugTag(get_class($obj) . " #preWidget [{$file_name}]");
            static::consoleDebug($data, $tag, 1);
        });  // 注册 组件渲染 打印组件变量  用于调试

        $apiResult && AbstractApi::on('apiResult', function (ApiEvent $event) {
            $obj = $event->getObject();
            $tag = $obj->getRequest()->debugTag(get_class($obj) . ' #apiResult');
            static::consoleDebug([
                'method' => $event->getAction(),
                'args' => $event->getArgs(),
                'result' => $event->getResult(),
                'callback' => $event->getCallback(),
            ], $tag);
        });

        $apiException && AbstractApi::on('apiException', function (ApiEvent $event) {
            $obj = $event->getObject();
            $tag = $obj->getRequest()->debugTag(get_class($obj) . ' #apiException');
            static::consoleDebug([
                'method' => $event->getAction(),
                'args' => $event->getArgs(),
                'exception' => $event->getException(),
                'callback' => $event->getCallback(),
            ], $tag);
            static::consoleException($event->getException());
        });

        $runSql && OrmConfig::on('runSql', function (OrmEvent $event) {
            list($sql_str, $args, $time, $_tag) = [$event->getSql(), $event->getArgs(), $event->getTime(), $event->getTag()];
            $time_str = round($time, 3) * 1000;
            static::consoleDebug([$sql_str, $args], "[SQL] {$_tag} <{$time_str}ms>", 1);
        });
    }

    protected static function tryFixParams($params)
    {
        $ret = [];
        foreach ($params as $key => $item) {
            if (is_object($item)) {
                $ret[$key] = Util::try2array($item);
            } else {
                $ret[$key] = $item;
            }
        }
        return $ret;
    }

}