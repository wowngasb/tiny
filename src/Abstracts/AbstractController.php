<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/24 0024
 * Time: 14:59
 */

namespace Tiny\Abstracts;

use Tiny\Application;
use Tiny\Traits\CacheTrait;
use Tiny\Traits\EventTrait;
use Tiny\Traits\LogTrait;
use Tiny\Traits\RpcTrait;
use Tiny\Request;
use Tiny\Response;
use Tiny\ViewInterface;

/**
 * Class Controller
 * @package Tiny
 */
abstract class AbstractController extends AbstractContext
{
    protected $_view = null;

    protected $routeInfo = [];  // 在路由完成后, 请求被分配到的路由信息 [$module, $controller, $action]
    protected $appname = '';

    protected $_layout_tpl = '';

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->routeInfo = $request->getRouteInfo();

        $this->appname = Application::app()->getName();
    }

    public function setLayout($layout_tpl)
    {
        $this->_layout_tpl = $layout_tpl;
    }

    /**
     * Controller 构造完成之后 具体action 之前调佣 通常用于初始化 需显示调用父类 beforeAction
     */
    public function beforeAction()
    {
        // do nothing
    }

    /**
     * 为 Controller 绑定模板引擎
     * @param ViewInterface $view 实现视图接口的模板引擎
     * @return $this
     */
    final protected function setView(ViewInterface $view)
    {
        $this->_view = $view;
        return $this;
    }

    /**
     * @return ViewInterface
     */
    final protected function getView()
    {
        return $this->_view;
    }

    /**
     * 添加 模板变量
     * @param mixed $name 字符串或者关联数组, 如果为字符串, 则$value不能为空, 此字符串代表要分配的变量名. 如果为数组, 则$value须为空, 此参数为变量名和值的关联数组.
     * @param mixed $value 分配的模板变量值
     * @return $this
     */
    final protected function assign($name, $value = null)
    {
        $this->getView()->assign($name, $value);
        return $this;
    }

    /**
     * @param string $tpl_path
     */
    abstract protected function display($tpl_path = '');

    abstract protected function widget($tpl_path, array $params = []);

    /**
     *  注册回调函数  回调参数为 callback($this, $tpl_path, $params)
     *  1、preDisplay    在模板渲染之前触发
     *  2、preWidget    在组件渲染之前触发
     * @param string $event
     * @return bool
     */
    protected static function isAllowedEvent($event)
    {
        static $allow_event = ['preDisplay', 'preWidget',];
        return in_array($event, $allow_event);
    }

}