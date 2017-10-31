<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/1 0001
 * Time: 19:57
 */

namespace Tiny\Abstracts;

use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;


/**
 * Interface ExecutableEmptyInterface
 * 一个空的接口  实现此接口的类 才可以被分发器执行
 * @package Tiny
 */
abstract class AbstractContext extends AbstractModel
{

    private $_request = null;
    private $_response = null;
    private $_action_name = '';

    /**
     * BaseContext constructor.
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(RequestInterface $request, ResponseInterface $response)
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

    public function _getActionName()
    {
        return $this->_action_name;
    }

    public function _setActionName($action_name)
    {
        $this->_action_name = $action_name;
    }

    /**
     * @return null|RequestInterface
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @return null|ResponseInterface
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

    public function all_get()
    {
        return $this->_request->all_get();
    }

    public function all_post()
    {
        return $this->_request->all_post();
    }

    public function all_env()
    {
        return $this->_request->all_env();
    }

    public function all_server()
    {
        return $this->_request->all_server();
    }

    public function all_cookie()
    {
        return $this->_request->all_cookie();
    }

    public function all_files()
    {
        return $this->_request->all_files();
    }

    public function all_request()
    {
        return $this->_request->all_request();
    }

    public function all_session()
    {
        return $this->_request->all_session();
    }

}