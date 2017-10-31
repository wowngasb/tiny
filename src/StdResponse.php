<?php

namespace Tiny;

use Tiny\Exception\AppStartUpError;
use Tiny\Interfaces\ResponseInterface;

/**
 * Class StdResponse
 * 默认 StdResponse 设置 header 输出响应 使用默认 header 函数
 * @package Tiny
 */
abstract class StdResponse implements ResponseInterface
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
        if (!is_null($http_response_code)) {
            $this->setResponseCode(intval($http_response_code));
        }
        return $this;
    }

    /**
     * @return $this
     * @throws AppStartUpError
     */
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

    /**
     * @param $code
     * @return $this
     * @throws AppStartUpError
     */
    public function setResponseCode($code)
    {
        if ($this->_header_sent) {
            throw new AppStartUpError('header has been send');
        }
        $this->_code = intval($code);
        return $this;
    }

    /**
     * 发送响应header给请求端 只有第一次发送有效 多次发送不会出现异常
     * @return $this
     */
    public function sendHeader()
    {
        if (!$this->_header_sent) {
            foreach ($this->_header_list as $idx => $val) {
                header($val[0], $val[1], $val[2]);
            }
            http_response_code($this->_code);
            $this->_header_sent = true;
        }
        return $this;
    }

    /**
     * 向请求回应 添加消息体
     * @param string $msg 要发送的字符串
     * @param string $name 此次发送消息体的 名称 可用于debug 或者 调整输出顺序
     * @return $this
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

    /**
     * @return void
     */
    public function end()
    {
        $this->send();
        exit();
    }

    public function send()
    {
        if (!$this->_header_sent) {
            $this->sendHeader();
        }
        foreach ($this->yieldBody() as $html) {
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
        require($tpl_file);
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