<?php

namespace Tiny\Traits;

use Tiny\Plugin\ApiHelper;

trait RpcTrait
{

    protected static $_yar_hub_class = 'YarApiHub';  //YAR并行调用HUB类，需放置在api目录下
    protected static $_yar_hub_method = '_yarHub';
    protected static $_yar_ret_list = [];
    protected static $callinfo = '';

    #####################################
    ########### 对外接口 提供连续调用API ###########
    #####################################

    /**
     * 连续调用多个接口 异步使用时会调用session_write_close释放session锁，子调用不可改写session
     * @param $class_name  String  类名 带完整的命名空间
     * @param $method_name  String  需要调用的方法名
     * @param $params_arr  array  方法参数 参数名=>参数值，默认参数自动补全
     * @param $init_params  array  类型 构造函数参数 默认为空
     * @return array []
     */
    public static function _multipleApi($class_name, $method_name, $params_arr, $init_params = [])
    {
        if (defined('ASYNC_RPC_HANDEL') && ASYNC_RPC_HANDEL == 'YAR') {  //yar异步调用
            self::$_yar_ret_list = [];
            return self::_yarApi($class_name, $method_name, $params_arr, $init_params);
        } else {
            self::$_yar_ret_list = [];
            return self::_syncApi($class_name, $method_name, $params_arr, $init_params);
        }
    }

    public function _getApiRst()
    {
        return self::$_yar_ret_list;
    }

    public function _clearApiRst()
    {
        self::$_yar_ret_list = [];
    }

    #####################################
    ########### RPC 的具体实现 ##############  TODO
    #####################################

    protected static function _syncApi($class_name, $method_name, $params_arr, $init_params = [])
    {
        foreach ($params_arr as $key => $val) {
            self::$_yar_ret_list[] = [];
        }
        return self::$_yar_ret_list;
    }

    protected static function _yarApi($class_name, $method_name, $params_arr, $init_params = [])
    {
        if (empty($method_name) || empty($params_arr) || !is_array($params_arr) ||
            empty(self::$_yar_hub_class) || empty(self::$_yar_hub_method) ||
            !ApiHelper::_hasMethod($class_name, $method_name)
        ) {
            return [];
        }

        $yar_callback = function ($retval, $callinfo) {
            self::$callinfo = $callinfo;
            self::$_yar_ret_list[] = $retval;
        };
        \Yar_Concurrent_Client::reset();
        $yar_api_host = "";
        foreach ($params_arr as $key => $val) {
            $args = ['class_name' => $class_name, 'method_name' => $method_name, 'params' => $val, 'init_params' => $init_params, 'uptime' => microtime(true),];  //参数只有30秒有效期
            \Yar_Concurrent_Client::call($yar_api_host, self::$_yar_hub_method, [json_encode($args),], $yar_callback);
        }
        \Yar_Concurrent_Client::loop(); //send
        return self::$_yar_ret_list;
    }

    protected static function _decodeArgs($_params)
    {
        if (empty($_params)) {
            return [];
        }
        if (empty($params)) {
            return [];
        }
        return json_decode($params, true);
    }

}