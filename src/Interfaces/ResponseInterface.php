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
     * 发送响应header给请求端 只有第一次发送有效 多次发送不会出现异常
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
     * @return void
     */
    public function end();

    /**
     *  执行给定模版文件和变量数组 渲染模版 动态渲染模版文件 依靠 response 完成
     * @param string $tpl_file 模版文件 绝对路径
     * @param array $data 变量数组  变量会释放到 模版文件作用域中
     * @return string
     * @throws AppStartUpError
     */
    public function requireForRender($tpl_file, array $data = []);

    /**
     * @return void
     */
    public function ob_start();

    /**
     * @return string
     */
    public function ob_get_clean();

    public function send();

}