<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/24 0024
 * Time: 14:59
 */

namespace Tiny\Abstracts;

use Tiny\Application;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;
use Tiny\Interfaces\ViewInterface;

/**
 * Class Controller
 * @package Tiny
 */
abstract class AbstractController extends AbstractContext
{
    private $_view = null;
    private $_layout = '';

    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        parent::__construct($request, $response);
    }

    final protected function setLayout($layout_tpl)
    {
        $this->_layout = $layout_tpl;
    }

    final public function getLayout()
    {
        return $this->_layout;
    }

    protected function extendAssign(array $params)
    {
        $request = $this->getRequest();
        $params['routeInfo'] = $request->getRouteInfo();
        $params['app'] = Application::app();
        $params['request'] = $request;
        $params['ctrl'] = $this;
        return $params;
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