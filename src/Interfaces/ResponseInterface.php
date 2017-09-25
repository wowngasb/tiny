<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/25
 * Time: 14:47
 */

namespace Tiny\Interfaces;


use Tiny\Exception\AppStartUpError;

interface ResponseInterface
{

    /**
     * 添加响应header
     * @param string $string
     * @param bool $replace [optional]
     * @param int $http_response_code [optional]
     * @return $this
     * @throws \Exception HeaderError
     */
    public function addHeader($string, $replace = true, $http_response_code = null);

    public function resetResponse();

    public function setResponseCode($code);

    /**
     * 发送响应header给请求端
     * @return $this
     * @throws AppStartUpError
     */
    public function sendHeader();

    /**
     * 向请求回应 添加消息体
     * @param string $msg 要发送的字符串
     * @param string $name 此次发送消息体的 名称 可用于debug
     * @return $this
     */
    public function appendBody($msg, $name = '');

    /**
     * @return $this
     */
    public function sendBody();

    /**
     * @param string|null $name
     * @return array
     */
    public function getBody($name = null);

    /**
     * @param string|null $name
     * @return $this
     */
    public function clearBody($name = null);

}