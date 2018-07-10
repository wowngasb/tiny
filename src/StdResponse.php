<?php

namespace Tiny;

use JsonSerializable;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tiny\Exception\AppStartUpError;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;

/**
 * Class StdResponse
 * 默认 StdResponse 设置 header 输出响应 使用默认 header 函数
 * @package Tiny
 */
class StdResponse extends SymfonyResponse implements ResponseInterface
{

    protected $_header_list = [];  // 响应给请求的Header
    protected $_header_sent = false;  // 响应Header 是否已经发送
    protected $_code = 200;  // 响应给请求端的HTTP状态码
    protected $_header_send = 0;
    protected $_body_send = 0;
    protected $_body = [];  // 响应给请求的body

    /** @var RequestInterface */
    protected $_request = null;

    public function __construct($content = '', $status = 200, array $headers = [])
    {
        parent::__construct($content, $status, $headers);
    }

    public function bindingRequest(RequestInterface $request)
    {
        if (!is_null($this->_request)) {
            throw new AppStartUpError('bindingRequest only run once');
        }
        $this->_request = $request;
    }

    /**
     * Get the scheme for a raw URL.
     *
     * @param  bool|null $secure
     * @return string
     */
    public function getScheme($secure = null)
    {
        if (is_null($secure)) {
            return $this->_request->schema();
        }
        return $secure ? 'https' : 'http';
    }

    /**
     * Create a new redirect response to the given path.
     *
     * @param  string $path
     * @param  int $status
     * @param  array $headers
     * @param  bool $secure
     * @return StdResponse
     */
    public function to($path, $status = 302, array $headers = [], $secure = null)
    {
        $path = trim($path);
        if (Util::stri_startwith($path, 'http://') || Util::stri_startwith($path, 'https://')) {
            $url = $path;
        } else {
            $schema = $this->getScheme($secure);
            $host = $this->_request->host();
            while (!empty($path) && Util::str_startwith($path, '/')) {
                $path = substr($path, 1);
            }
            $url = "{$schema}://{$host}/{$path}";
        }

        $this->resetResponse()->setResponseCode($status)->addHeader("Location: {$url}", true);
        foreach ($headers as $header) {
            $this->addHeader($header);
        }
        return $this;
    }

    /**
     * Create a new redirect response to the previous location.
     *
     * @param  int $status
     * @param  array $headers
     * @return StdResponse
     */
    public function back($status = 302, array $headers = [])
    {
        $back = $this->_request->getHttpReferer();
        $back = !empty($back) ? $back : '/';
        return $this->to($back, $status, $headers);
    }

    /**
     * Create a new redirect response, while putting the current URL in the session.
     *
     * @param  string $path
     * @param  int $status
     * @param  array $headers
     * @param  bool|null $secure
     * @return StdResponse
     */
    public function guest($path, $status = 302, $headers = [], $secure = null)
    {
        $this->_request->set_session('url.intended', $this->_request->full());

        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Flash an array of input to the session.
     *
     * @param  array $input
     * @return StdResponse
     */
    public function withInput(array $input = null)
    {
        if (is_null($input)) {
            $input = $this->_request->all_request();
        }
        $this->_request->set_session('_input', $input);
        return $this;
    }

    /**
     * Flash an array of input to the session.
     *
     * @param  mixed  string
     * @return StdResponse
     */
    public function onlyInput()
    {
        $only = func_get_args();
        $_request = $this->_request->all_request();
        $input = [];
        foreach ($_request as $key => $item) {
            if (in_array($key, $only)) {
                $input[$key] = $item;
            }
        }
        return $this->withInput($input);
    }

    /**
     * Flash an array of input to the session.
     *
     * @param  mixed  string
     * @return StdResponse
     */
    public function exceptInput()
    {
        $except = func_get_args();
        $_request = $this->_request->all_request();
        $input = [];
        foreach ($_request as $key => $item) {
            if (!in_array($key, $except)) {
                $input[$key] = $item;
            }
        }
        return $this->withInput($input);
    }

    /**
     * Flash a container of errors to the session.
     *
     * @param  array|string $error
     * @param  string $key
     * @return StdResponse
     */
    public function withErrors($error, $key = 'default')
    {
        $errors = $this->_request->_session('_errors', []);
        $errors = array_merge($errors, is_array($error) ? $error : [$key => $error]);
        $this->_request->set_session('_errors', $errors);
        return $this;
    }

    /**
     * Add multiple cookies to the response.
     *
     * @param  array $cookies
     * @return StdResponse
     */
    public function withCookies(array $cookies)
    {
        foreach ($cookies as $key => $value) {
            $this->_request->set_cookie($key, $value);
            $this->_request->setcookie($key, $value);
        }

        return $this;
    }

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
     * @return StdResponse
     * @internal param mixed|\Symfony\Component\HttpFoundation\Cookie $cookie
     */
    public function withCookie($name, $value, $expire = 0, $path = "", $domain = "", $secure = false, $httponly = false)
    {
        $this->_request->set_cookie($name, $value);
        $this->_request->setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        return $this;
    }

    /**
     * Flash a piece of data to the session.
     *
     * @param  string|array $key
     * @param  mixed $value
     * @return StdResponse
     */
    public function with($key, $value = null)
    {
        $key = is_array($key) ? $key : [$key => $value];

        foreach ($key as $k => $v) {
            $this->_request->set_session($k, $v);
        }

        return $this;
    }

    public function old($name, $default = '')
    {
        $old_input = $this->_request->_session('_old_input', []);
        return Util::v($old_input, $name, $default);
    }

    public function input_clear()
    {
        $this->_request->del_session('_input');
    }

    public function errors_clear()
    {
        $this->_request->del_session('_errors');
    }

    public function errors_has($name)
    {
        $name = trim($name);
        if (empty($name)) {
            return false;
        }
        $last_errors = $this->_request->_session('_errors', []);
        return isset($last_errors[$name]);
    }

    public function errors_first($name, $format = ':message', $default = '')
    {
        $last_errors = $this->_request->_session('_errors', []);
        $error_msg = Util::v($last_errors, $name, $default);
        $format = is_string($format) ? [$format] : (array)$format;
        foreach ($format as $item) {
            $fv = Util::v($last_errors, substr($item, 1), '');
            $error_msg = str_replace($item, $fv, $error_msg);
        }
        return $error_msg;
    }

    /**
     * Return a new JSON response from the application.
     *
     * @param  string|array $data
     * @param  int $status
     * @param  array $headers
     * @param  int $options
     * @return StdResponse
     */
    public function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        if (is_string($data) || is_integer($data) || is_double($data)) {
            $json_str = "$data";
        } elseif ($data instanceof JsonSerializable) {
            $json_str = json_encode($data, $options);
        } elseif (is_callable([$data, 'toArray'])) {
            $_data = call_user_func_array([$data, 'toArray'], []);
            $json_str = json_encode($_data, $options);
        } elseif (is_callable([$data, 'toJson'])) {
            $json_str = call_user_func_array([$data, 'toJson'], []);
        } else {
            $json_str = json_encode($data, $options);
        }
        $this->resetResponse()->setResponseCode($status);
        if (!empty($this->_callback)) {
            $jsonp_str = "{$this->_callback}({$json_str});";
            $this->appendBody($jsonp_str, '_jsonp');
        } else {
            $this->appendBody($json_str, '_json');
        }

        foreach ($headers as $header) {
            $this->addHeader($header);
        }
        return $this;
    }

    private $_callback = '';

    /**
     * Sets the JSONP callback.
     * @param string|null $callback The JSONP callback or null to use none
     * @return StdResponse
     * @throws AppStartUpError When the callback name is not valid
     */
    public function setCallback($callback = null)
    {
        if (is_null($callback)) {
            $callback = $this->_request->_request('callback', '');
        }

        // taken from http://www.geekality.net/2011/08/03/valid-javascript-identifier/
        $pattern = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';
        $parts = explode('.', $callback);
        foreach ($parts as $part) {
            if (!preg_match($pattern, $part)) {
                throw new AppStartUpError('The callback name is not valid.');
            }
        }

        $this->_callback = $callback;
        if (!empty($this->_body['_json']) && !empty($this->_callback)) {
            $json_str = $this->_body['_json'];
            $this->resetBody();
            $jsonp_str = "{$this->_callback}({$json_str});";
            $this->appendBody($jsonp_str, '_jsonp');
        }
        return $this;
    }

    /**
     * 添加响应header
     * @param string $string
     * @param bool $replace [optional]
     * @param int $http_response_code [optional]
     * @return StdResponse
     * @throws \Exception HeaderError
     */
    public function addHeader($string, $replace = true, $http_response_code = null)
    {
        /*
        if ($this->_header_sent) {
            throw new AppStartUpError('header has been send');
        }*/
        $this->_header_list[] = [$string, $replace, $http_response_code];
        if (!is_null($http_response_code)) {
            $this->setResponseCode(intval($http_response_code));
        }
        return $this;
    }

    /**
     * @return StdResponse
     * @throws AppStartUpError
     */
    public function resetResponse()
    {
        $this->resetHeader();
        $this->resetBody();
        return $this;
    }

    /**
     * @param $code
     * @return StdResponse
     * @throws AppStartUpError
     */
    public function setResponseCode($code)
    {
        /*
        if ($this->_header_sent) {
            throw new AppStartUpError('header has been send');
        }*/
        $this->_code = intval($code);
        return $this;
    }

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->_code;
    }

    /**
     * @return int
     */
    public function getHeaderSendLength()
    {
        return $this->_header_send;
    }

    /**
     * @return int
     */
    public function getBodySendLength()
    {
        return $this->_body_send;
    }

    /**
     * 发送响应header给请求端 只有第一次发送有效 多次发送不会出现异常
     * @return StdResponse
     */
    public function sendHeader()
    {
        if (!empty($this->_body['_json'])) {
            $this->addHeader('Content-Type:application/json');
        }
        if (!empty($this->_body['_jsonp'])) {
            $this->addHeader('Content-Type:application/javascript');
        }
        if (!$this->_header_sent) {
            foreach ($this->_header_list as $idx => $val) {
                $this->_header_send += strlen($val[0]);
                header($val[0], $val[1], $val[2]);
            }
            http_response_code($this->_code);
            $this->_header_sent = true;
        }
        return $this;
    }

    /**
     * @return StdResponse
     * @throws AppStartUpError
     */
    public function resetHeader()
    {
        /*
        if ($this->_header_sent) {
            throw new AppStartUpError('header has been send');
        }*/

        $this->_header_list = [];
        $this->_code = 200;
        $this->_header_send = 0;
        return $this;
    }

    /**
     * 向请求回应 添加消息体
     * @param string $msg 要发送的字符串
     * @param string $name 此次发送消息体的 名称 可用于debug 或者 调整输出顺序
     * @return StdResponse
     */
    public function appendBody($msg, $name = 'main')
    {
        if (!isset($this->_body[$name])) {
            $this->_body[$name] = [];
        }
        $this->_body[$name][] = $msg;
        return $this;
    }

    /**
     * @return \Generator
     */
    public function yieldBody()
    {
        foreach ($this->_body as $name => $body) {
            foreach ($body as $idx => $msg) {
                yield $msg;
            }
        }
        $this->_body = [];
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
     * @return StdResponse
     */
    public function resetBody($name = null)
    {
        if (is_null($name)) {
            $this->_body = [];
        }
        unset($this->_body[$name]);
        $this->_body_send = 0;
        return $this;
    }

    /**
     * @param string $msg
     * @param bool $resetBody
     * @param bool $resetHeader
     * @return void
     */
    public function end($msg = '', $resetBody = false, $resetHeader = false)
    {
        if ($resetHeader) {
            $this->resetHeader();
        }
        if ($resetBody) {
            $this->resetBody();
        }
        if (!empty($msg)) {
            $this->appendBody($msg);
        }
        $this->send();
        exit();
    }

    public function send()
    {
        if (!$this->_header_sent) {
            $this->sendHeader();
        }
        foreach ($this->yieldBody() as $html) {
            $this->_body_send += strlen($html);
            echo $html;  // 输出 响应内容
        }
        $this->_body = [];
    }

    /**
     *  执行给定模版文件和变量数组 渲染模版 动态渲染模版文件 依靠 response 完成
     * @param string $tpl_file 模版文件 绝对路径
     * @param array $data 变量数组  变量会释放到 模版文件作用域中
     * @return string
     * @throws AppStartUpError
     */
    public function requireForRender($tpl_file, array $data = [])
    {
        $tpl_file = trim($tpl_file);
        if (empty($tpl_file)) {
            return '';
        }
        if (!is_file($tpl_file)) {
            throw new AppStartUpError("requireForRender cannot find {$tpl_file}");
        }
        extract($data, EXTR_OVERWRITE);

        $this->ob_start();
        require($tpl_file);  // 动态引入文件 得到字符串 用于渲染模版
        $buffer = $this->ob_get_clean();
        return $buffer !== false ? $buffer : '';
    }

    /**
     * @return void
     */
    public function ob_start()
    {
        ob_start();
    }

    /**
     * @return string
     */
    public function ob_get_clean()
    {
        return ob_get_clean();
    }
}