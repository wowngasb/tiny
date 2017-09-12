<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/1 0001
 * Time: 19:57
 */

namespace Tiny\Abstracts;

use Tiny\Request;
use Tiny\Response;
use Tiny\Traits\CacheTrait;
use Tiny\Traits\EventTrait;
use Tiny\Traits\LogTrait;
use Tiny\Traits\RpcTrait;


/**
 * Interface ExecutableEmptyInterface
 * 一个空的接口  实现此接口的类 才可以被分发器执行
 * @package Tiny
 */
abstract class AbstractContext
{
    use EventTrait, LogTrait, RpcTrait, CacheTrait;

    private $_request = null;
    private $_response = null;
    private $_action_name = '';

    /**
     * BaseContext constructor.
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->_request = $request;
        $this->_response = $response;
    }

    /**
     * 过滤 action 参数  子类按照顺序依次调用父类此方法
     * @param array $params
     * @return array 处理后的 API 执行参数 将用于调用方法
     */
    public function beforeAction(array $params)
    {
        return $params;
    }

    public function getActionName()
    {
        return $this->_action_name;
    }

    public function setActionName($action_name)
    {
        $this->_action_name = $action_name;
    }

    /**
     * @return null|Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @return null|Response
     */
    public function getResponse()
    {
        return $this->_response;
    }

    public function _get($name = null, $default = '')
    {
        return $this->_request->_get($name, $default);
    }

    public function _post($name = null, $default = '')
    {
        return $this->_request->_post($name, $default);
    }

    public function _env($name = null, $default = '')
    {
        return $this->_request->_env($name, $default);
    }

    public function _server($name = null, $default = '')
    {
        return $this->_request->_server($name, $default);
    }

    public function _cookie($name = null, $default = '')
    {
        return $this->_request->_cookie($name, $default);
    }

    public function _files($name = null, $default = '')
    {
        return $this->_request->_files($name, $default);
    }

    public function _request($name = null, $default = '')
    {
        return $this->_request->_request($name, $default);
    }

    public function _session($name = null, $default = '')
    {
        return $this->_request->_session($name, $default);
    }

}