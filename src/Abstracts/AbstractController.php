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
use Tiny\Util;

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

    protected function view($tpl_file, $data = [], $clearErrors = true, $clearInput = true)
    {
        $data = !empty($data) ? Util::try2array($data) : $data;

        $this->display($tpl_file, $data);
        if ($clearErrors) {
            $this->errors_clear();
        }
        if ($clearInput) {
            $this->input_clear();
        }
        return null;
    }

    /**
     * Get an instance of the redirector.
     * @param  string|null $to
     * @param  int $status
     * @param  array $headers
     * @param  bool $secure
     * @return ResponseInterface
     */
    protected function redirect($to = null, $status = 302, $headers = [], $secure = null)
    {
        if (is_null($to)) {
            return $this->getResponse()->resetResponse();
        }
        if (is_null($secure)) {
            $secure = $this->getRequest()->is_https();
        }
        return $this->getResponse()->resetResponse()->to($to, $status, $headers, $secure);
    }

    /**
     * @param  array|null $json
     * @param  int $status
     * @param  array $headers
     * @param  int $options
     * @return ResponseInterface
     */
    protected function response($json = null, $status = 200, $headers = [], $options = 0)
    {
        if (is_null($json)) {
            return $this->getResponse()->resetResponse();
        }
        return $this->getResponse()->resetResponse()->json($json, $status, $headers, $options);
    }

    public function old($name, $default = '')
    {
        return $this->getResponse()->old($name, $default);
    }

    public function input_clear()
    {
        $this->getResponse()->input_clear();
    }

    public function errors_clear()
    {
        $this->getResponse()->errors_clear();
    }

    public function errors_has($name)
    {
        return $this->getResponse()->errors_has($name);
    }

    public function errors_first($name, $format = ':message', $default = '')
    {
        return $this->getResponse()->errors_first($name, $format, $default);
    }

    final protected function _setLayout($layout_tpl)
    {
        $this->_layout = $layout_tpl;
    }

    final public function _getLayout()
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
     * @return AbstractController
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
     * @return AbstractController
     */
    final protected function assign($name, $value = null)
    {
        $this->getView()->assign($name, $value);
        return $this;
    }

    /**
     * @param string $tpl_path
     * @param array $params
     */
    abstract protected function display($tpl_path = '', array $params = []);

    ###############################################################
    ############## 重写 EventTrait::isAllowedEvent ################
    ###############################################################

    /**
     *  注册回调函数  回调参数为 callback(\Tiny\Event\ControllerEvent $event)
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