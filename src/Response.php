<?php

namespace Tiny;

use Tiny\Exception\AppStartUpError;
use Tiny\Interfaces\ResponseInterface;

/**
 * Class Response
 * @package Tiny
 */
class Response implements ResponseInterface
{

    protected $_header_list = [];  // 响应给请求的Header
    protected $_header_sent = false;  // 响应Header 是否已经发送
    protected $_code = 200;  // 响应给请求端的HTTP状态码
    protected $_body = [];  // 响应给请求的body

    public function __construct()
    {
    }

    /**
     * 添加响应header
     * @param string $string
     * @param bool $replace [optional]
     * @param int $http_response_code [optional]
     * @return $this
     * @throws \Exception HeaderError
     */
    public function addHeader($string, $replace = true, $http_response_code = null)
    {
        if ($this->_header_sent) {
            throw new AppStartUpError('header has been send');
        }
        $this->_header_list[] = [$string, $replace, $http_response_code];
        return $this;
    }

    public function resetResponse()
    {
        if ($this->_header_sent) {
            throw new AppStartUpError('header has been send');
        }
        $this->_body = [];
        $this->_header_list = [];
        $this->_code = 200;
        return $this;
    }

    public function setResponseCode($code)
    {
        if ($this->_header_sent) {
            throw new AppStartUpError('header has been send');
        }
        $this->_code = intval($code);
        return $this;
    }

    /**
     * 发送响应header给请求端
     * @return $this
     * @throws AppStartUpError
     */
    public function sendHeader()
    {
        if ($this->_header_sent) {
            throw new AppStartUpError('header has been send');
        }
        foreach ($this->_header_list as $idx => $val) {
            header($val[0], $val[1], $val[2]);
        }
        http_response_code($this->_code);
        $this->_header_sent = true;
        return $this;
    }

    /**
     * 向请求回应 添加消息体
     * @param string $msg 要发送的字符串
     * @param string $name 此次发送消息体的 名称 可用于debug
     * @return $this
     */
    public function appendBody($msg, $name = '')
    {
        if (!isset($this->_body[$name])) {
            $this->_body[$name] = [];
        }
        $this->_body[$name][] = $msg;
        return $this;
    }

    /**
     * @return $this
     */
    public function sendBody()
    {
        if (!$this->_header_sent) {
            $this->sendHeader();
        }
        foreach ($this->_body as $name => $body) {
            foreach ($body as $idx => $msg) {
                echo $msg;
            }
        }
        return $this;
    }

    /**
     * @param string|null $name
     * @return array
     */
    public function getBody($name = null)
    {
        if (is_null($name)) {
            return $this->_body;
        }
        return isset($this->_body[$name]) ? $this->_body[$name] : [];
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function clearBody($name = null)
    {
        if (is_null($name)) {
            $this->_body = [];
        }
        unset($this->_body[$name]);
        return $this;
    }

}