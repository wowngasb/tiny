<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/25
 * Time: 14:47
 */

namespace Tiny\Interfaces;


use Tiny\Exception\Interrupt;

interface ResponseInterface
{

    /**
     * 添加响应header
     * @param string $string
     * @param bool $replace [optional]
     * @param int $http_response_code [optional]
     * @return ResponseInterface
     * @throws \Exception HeaderError
     */
    public function addHeader($string, $replace = true, $http_response_code = null);

    /**
     * @return ResponseInterface
     */
    public function resetResponse();

    /**
     * @param $code
     * @return ResponseInterface
     */
    public function setResponseCode($code);

    /**
     * 发送响应header给请求端
     * @return ResponseInterface
     */
    public function sendHeader();

    /**
     * 向请求回应 添加消息体
     * @param string $msg 要发送的字符串
     * @param string $name 此次发送消息体的 名称 可用于debug
     * @return ResponseInterface
     */
    public function appendBody($msg, $name = '');

    /**
     * @return \Generator
     */
    public function yieldBody();

    /**
     * @param string|null $name
     * @return array
     */
    public function getBody($name = null);

    /**
     * @param string|null $name
     * @return ResponseInterface
     */
    public function clearBody($name = null);

    /**
     * @throws Interrupt
     */
    public function interrupt();

}