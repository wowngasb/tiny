<?php

namespace Tiny\Plugin;

use Tiny\Traits\LogTrait;
use Tiny\Util;

class ApiHelper
{

    use LogTrait;

    private static $ignore_method_dict = [
        'is_https' => 1,
        'log' => 1,
        'debug' => 1,
        'debugargs' => 1,
        'debugresult' => 1,
        'funcgetargs' => 1,
        'info' => 1,
        'warn' => 1,
        'error' => 1,
        'fatal' => 1,
        'beforeaction' => 1,
        'getrequest' => 1,
        'getresponse' => 1,
        'client_ip' => 1,
        'on' => 1,
        'path' => 1,
        'fullurl' => 1,
        'auth' => 1,
        'all_get' => 1,
        'all_post' => 1,
        'all_env' => 1,
        'all_server' => 1,
        'all_cookie' => 1,
        'all_files' => 1,
        'all_request' => 1,
        'all_session' => 1,
        'del_cookie' => 1,
        'del_env' => 1,
        'del_files' => 1,
        'del_get' => 1,
        'del_post' => 1,
        'del_request' => 1,
        'del_server' => 1,
        'del_session' => 1,
        'set_cookie' => 1,
        'set_env' => 1,
        'set_files' => 1,
        'set_get' => 1,
        'set_post' => 1,
        'set_request' => 1,
        'set_server' => 1,
        'set_session' => 1,
    ];

    public static function fixActionParams($obj, $func, $params)
    {
        if (!is_array($params)) {
            return $params;
        }

        $reflection = new \ReflectionMethod($obj, $func);
        $args = self::fix_args(self::getApiMethodArgs($reflection), $params);
        return $args;
    }

    private static function fix_args($param, $args_input)
    {  //根据函数的参数设置和$args_input修复默认参数并调整API参数顺序
        $tmp_args = [];
        foreach ($param as $key => $arg) {
            $arg_name = $arg['name'];
            if (isset($args_input[$arg_name])) {
                $tmp = $args_input[$arg_name];
                if ($arg['isArray']) {
                    if (!is_array($tmp) && $tmp !== '') {
                        $tmp = [$tmp];   //参数要求为数组，把单个参数包装为数组
                    }
                    $default = !empty($arg['isOptional']) ? $arg['defaultValue'] : [];   //参数未给出时优先使用函数的默认参数，如果无默认参数这设置为 空数组
                    $tmp = is_array($tmp) && !empty($tmp) ? $tmp : $default;
                }
                $tmp_args[$arg_name] = $tmp;
            } else {
                $default = $arg['isOptional'] ? $arg['defaultValue'] : '';   //参数未给出时优先使用函数的默认参数，如果无默认参数这设置为空字符串
                if ($arg['isArray']){
                    $default = !empty($arg['isOptional']) ? $arg['defaultValue'] : [];   //参数未给出时优先使用函数的默认参数，如果无默认参数这设置为 空数组
                }
                $tmp_args[$arg_name] = $default;
            }
        }
        return $tmp_args;
    }

    public static function _getClassName($class_name)
    {
        $tmp = explode('\\', $class_name);
        return end($tmp);
    }

    public static function _hasMethod($class_name, $method_name)
    {
        $class_name = strval($class_name);
        $method_name = strval($method_name);
        if (empty($class_name) || empty($method_name)) {
            return false;
        }
        $rc = new \ReflectionClass($class_name);
        return $rc->hasMethod($method_name);
    }

    public static function model2js($cls, $method_list, $dev_debug = true)
    {
        $date_str = date('Y-m');
        $_dev_debug = $dev_debug ? 'true' : 'false';
        $log_msg = "build API.js@{$cls}";
        self::debug($log_msg, __METHOD__, __CLASS__, __LINE__);
        $js_str = <<<EOT
define('static/api/{$cls}', function(require, exports, module) {

  var global = typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {};
  
/*!
 * {$cls}.js
 * Auto Create By ApiHelper
 * build at {$date_str}
 */
(function (global, factory) {
	typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
	typeof define === 'function' && define.amd ? define(factory) :
	(global.{$cls} = factory());
}(this, (function () { 'use strict';

/*  */

function {$cls}Helper(){
    var self = this;
    this.debug = {$_dev_debug};
    
    var _h = window.location.host.toLowerCase();
    var _s = 'https:' === window.location.protocol ? 'https' : 'http';
    var _l = (typeof console !== "undefined" && typeof console.log === "function") ? {
        DEBUG: typeof console.debug === "function" ? console.debug.bind(console) : console.log.bind(console),
        INFO: typeof console.info === "function" ? console.info.bind(console) : console.log.bind(console),
        WARN: typeof console.warn === "function" ? console.warn.bind(console) : console.log.bind(console),
        ERROR: typeof console.error === "function" ? console.error.bind(console) : console.log.bind(console),
    } : {};
    var _d = function(){
        var now = new Date(new Date().getTime());
        var year = now.getFullYear();
        var month = now.getMonth()+1;
        var date = now.getDate();
        var hour = now.getHours();
        var minute = now.getMinutes();
        if(minute < 10){ minute = '0' + minute.toString(); } 
        var seconds = now.getSeconds();
        if(seconds < 10){ seconds = '0' + seconds.toString(); }
        return year+"-"+month+"-"+date+" "+hour+":"+minute+":"+seconds;
    };
    
    this._ajax = function (host, path, args, success, failure, logHandler, logLevelHandler, fixArgs) {
        var self = this;
        args = args || {};
        fixArgs = fixArgs || {};
        logHandler = logHandler || function (logLevel, use_time, args, data) {
            logLevel in _l && (_l[logLevel])(_d(), '[' + logLevel + '] ' + path + '(' + use_time + 'ms)', 'args:', args, 'data:', data)
        };
        logLevelHandler = logLevelHandler || function (res) {
            return (res.code) ? ( res.code === 0 ? 'INFO' : 'ERROR') : (!res.error ? 'INFO' : 'ERROR');
        };
    
        var api_url = _s + "://" + host + path,
            start_time = new Date().getTime();
            
        if( typeof CSRF_TOKEN !== "undefined" && CSRF_TOKEN ){
            args.csrf = CSRF_TOKEN;
        }
        
        return $.ajax($.extend({}, {
            type: host == window.location.host.toLowerCase() ? "POST" : "GET",
            url: api_url,
            data: args,
            cache: false,
            dataType: host === location.host.toLowerCase() ? "json" : "jsonp",
            error: function(xhr, status, error){
                typeof failure === 'function' && failure({
                    xhr: xhr, status: status, error: error
                });
            },
            success: function (res) {
                typeof logHandler === 'function' && logHandler(logLevelHandler(res), Math.round((new Date().getTime() - start_time)), args, res);
                var code = typeof res.code !== 'undefined' ? parseInt(res.code) : -1
                if (code === 0 || (code === -1 && !res.error)) {
                    typeof success === 'function' && success(res);
                } else {
                    typeof failure === 'function' && failure(res);
                }
            }
        }, fixArgs));
    };

EOT;

        foreach ($method_list as $key => $val) {
            $name = $val['name'];
            $doc_str = $dev_debug ? $val['doc'] : '';
            $args = json_encode(self::getExampleArgsByParameters($val['param']));
            $args_str = "this.{$name}_args = {$args};";
            $func_item = <<<EOT

    {$doc_str}
    this.{$name} = function(args, success, failure, logHandler, logLevelHandler, fixArgs) {
        var _p = '/api/{$cls}/{$name}';args = args || {};
        logHandler = logHandler || function (t, u, a, d) {
            self.debug && t in _l && (_l[t])(_d(),'['+t+'] '+_p+'('+u+'ms)','args:',a,'data:',d);
        };
        return !success && Promise ? new Promise(function(resolve, reject){
            self._ajax(_h, _p, args, resolve, reject, logHandler, logLevelHandler, fixArgs);
        }) : self._ajax(_h, _p, args, success, failure, logHandler, logLevelHandler, fixArgs);
    };
    {$args_str}

EOT;
            $js_str .= $func_item;
        }
        $js_str .= <<<EOT
}

/*  */

return new {$cls}Helper();
})));

});
EOT;
        return $js_str;
    }

    public static function getExampleArgsByParameters($param)
    {
        $tmp_args = [];
        foreach ($param as $key => $arg) {
            $name = $arg['name'];
            $tmp = '?';
            $tmp = $arg['isArray'] ? ['?', '...',] : $tmp;
            $tmp = $arg['isOptional'] ? $arg['defaultValue'] : $tmp;
            $tmp_args[$name] = $tmp;
        }
        return empty($tmp_args) ? null : $tmp_args;
    }

    public static function getApiParamList($class_name, $method)
    {
        if (empty($class_name) || empty($method)) {
            return [];
        }
        $reflection = new \ReflectionMethod($class_name, $method);
        $param = $reflection->getParameters();
        $tmp_args = [];
        foreach ($param as $arg) {
            $name = $arg->name;
            $tmp = ['name' => $name];
            $tmp['is_array'] = $arg->isArray();
            $tmp['is_optional'] = $arg->isOptional();
            $tmp['optional'] = $tmp['is_optional'] ? $arg->getDefaultValue() : '';
            $tmp_args[] = $tmp;
        }
        return $tmp_args;
    }

    public static function getApiNoteStr($class_name, $method)
    {
        if (empty($class_name) || empty($method)) {
            return '';
        }
        $reflection = new \ReflectionMethod($class_name, $method);
        return $reflection->getDocComment();
    }

    public static function getApiMethodList($class_name)
    {
        if (empty($class_name)) {
            return [];
        }
        $class = new \ReflectionClass($class_name);
        $method_list = [];
        $all_method_list = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($all_method_list as $key => $val) {
            $name = strtolower($val->getName());
            if (self::isIgnoreMethod($name)) {
                continue;
            } else {
                $doc = $val->getDocComment();
                $main_doc = Util::getMainDoc($doc);
                $method_list[] = [
                    'name' => $val->getName(),
                    'doc' => $doc,
                    'main_doc' => $main_doc,
                    'param' => self::getApiMethodArgs($val),
                ];
            }
        }
        return $method_list;
    }

    public static function getApiMethodArgs(\ReflectionMethod $reflection)
    {
        $param_obj = [];
        foreach ($reflection->getParameters() as $p) {
            $isOptional = $p->isOptional();
            $param_obj[] = [
                'name' => $p->name,
                'isArray' => $p->isArray(),
                'isOptional' => $isOptional,
                'defaultValue' => $isOptional ? $p->getDefaultValue() : null,
            ];
        }
        return $param_obj;
    }

    public static function isIgnoreMethod($name)
    {
        if ($name == '__construct' || stripos($name, 'hook', 0) === 0 || stripos($name, 'crontab', 0) === 0 || stripos($name, '_', 0) === 0) {
            return true;
        }
        $name = strtolower($name);
        return (isset(self::$ignore_method_dict[$name]) && !empty(self::$ignore_method_dict[$name]));
    }

    public static function getApiFileList($path, $base_path = '')
    {
        if (empty($base_path)) {
            $base_path = $path;
        }

        if (!is_dir($path) || !is_readable($path)) {
            return [];
        }

        $result = [];
        $allfiles = scandir($path);  //获取目录下所有文件与文件夹 
        foreach ($allfiles as $key => $filename) {  //遍历一遍目录下的文件与文件夹 
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            $fullname = $path . '/' . $filename;  //得到完整文件路径
            $file_item = [
                'name' => $filename,
                'fullname' => $fullname,
                'ctime' => filectime($fullname),
                'mtime' => filemtime($fullname),
                'path' => str_replace($base_path, '', $fullname),
            ];
            if (is_file($fullname)) {
                $file_item['type'] = 'file';
                $file_item['size'] = filesize($fullname);
                $result[] = $file_item;
            }
        }
        return $result;
    }

} 