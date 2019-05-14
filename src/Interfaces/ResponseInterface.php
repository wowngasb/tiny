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
     * 绑定 RequestInterface
     * @param RequestInterface $request
     */
    public function bindingRequest(RequestInterface $request);

    /**
     * Get the scheme for a raw URL.
     *
     * @param  bool|null $secure
     * @return string
     */
    public function getScheme($secure = null);

    /**
     * Create a new redirect response to the given path.
     *
     * @param  string $path
     * @param  int $status
     * @param  array $headers
     * @param  bool|null $secure
     * @return self
     */
    public function to($path, $status = 302, array $headers = [], $secure = null);

    /**
     * Create a new redirect response to the previous location.
     *
     * @param  int $status
     * @param  array $headers
     * @return self
     */
    public function back($status = 302, array $headers = []);

    /**
     * Create a new redirect response, while putting the current URL in the session.
     *
     * @param  string $path
     * @param  int $status
     * @param  array $headers
     * @param  bool|null $secure
     * @return self
     */
    public function guest($path, $status = 302, $headers = [], $secure = null);

    /**
     * Flash an array of input to the session.
     *
     * @param  array $input
     * @return ResponseInterface
     */
    public function withInput(array $input = null);

    /**
     * Flash an array of input to the session.
     *
     * @param  mixed  string
     * @return ResponseInterface
     */
    public function onlyInput();

    /**
     * Flash an array of input to the session.
     *
     * @param  mixed  string
     * @return ResponseInterface
     */
    public function exceptInput();

    /**
     * Flash a container of errors to the session.
     *
     * @param  array|string $provider
     * @param  string $key
     * @return ResponseInterface
     */
    public function withErrors($provider, $key = 'default');

    /**
     * Add multiple cookies to the response.
     *
     * @param  array $cookies
     * @return ResponseInterface
     */
    public function withCookies(array $cookies);

    /**
     * Add a cookie to the response.
     *
     * @param $name
     * @param $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return ResponseInterface
     * @internal param mixed|\Symfony\Component\HttpFoundation\Cookie $cookie
     */
    public function withCookie($name, $value, $expire = 0, $path = "", $domain = "", $secure = false, $httponly = false);

    /**
     * Flash a piece of data to the session.
     *
     * @param  string|array $key
     * @param  mixed $value
     * @return ResponseInterface
     */
    public function with($key, $value = null);

    public function old($name, $default = '');

    public function input_clear();

    public function errors_clear();

    public function errors_has($name);

    public function errors_first($name, $format = ':message', $default = '');

    /**
     * Return a new JSON response from the application.
     *
     * @param  string|array $data
     * @param  int $status
     * @param  array $headers
     * @param  int $options
     * @return ResponseInterface
     */
    public function json($data = [], $status = 200, array $headers = [], $options = 0);

    /**
     * Sets the JSONP callback.
     * @param string|null $callback The JSONP callback or null to use none
     * @return ResponseInterface
     * @throws \InvalidArgumentException When the callback name is not valid
     */
    public function setCallback($callback = null);

    /**
     * 添加响应header
     * @param string $string
     * @param bool $replace [optional]
     * @param int $http_response_code [optional]
     * @return self
     * @throws \Exception HeaderError
     */
    public function addHeader($string, $replace = true, $http_response_code = null);

    /**
     * @return self
     */
    public function resetResponse();

    /**
     * @param $code
     * @return self
     */
    public function setResponseCode($code);

    /**
     * @return int
     */
    public function getResponseCode();

    /**
     * @return int
     */
    public function getHeaderSendLength();

    /**
     * @return int
     */
    public function getBodySendLength();

    /**
     * 发送响应header给请求端 只有第一次发送有效 多次发送不会出现异常
     * @return self
     */
    public function sendHeader();

    /**
     * 重置 缓存的 header  如果 header 已经发送 抛出异常
     * @return self
     * @throws AppStartUpError
     */
    public function resetHeader();

    /**
     * 向请求回应 添加消息体
     * @param string $msg 要发送的字符串
     * @param string $name 此次发送消息体的 名称 可用于debug
     * @return self
     */
    public function appendBody($msg, $name = '');

    /**
     * @return \Generator
     */
    public function yieldBody();

    /**
     * @param string|null $name
     * @return array | string
     */
    public function getBody($name = null);

    /**
     * @param string|null $name
     * @return ResponseInterface
     */
    public function resetBody($name = null);

    /**
     * @param string $msg
     * @param bool $resetBody
     * @param bool $resetHeader
     */
    public function end($msg = '', $resetBody = false, $resetHeader = false);

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