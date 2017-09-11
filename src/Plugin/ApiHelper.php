<?php

namespace Tiny\Plugin;

use Tiny\Traits\LogTrait;

class ApiHelper
{

    use LogTrait;

    private static $ignore_method_dict = [
        'log' => 1,
        'debug' => 1,
        'debugargs' => 1,
        'debugresult' => 1,
        'info' => 1,
        'warn' => 1,
        'error' => 1,
        'fatal' => 1,
        'beforeapi' => 1,
        'beforeaction' => 1,
        'getrequest' => 1,
        'getresponse' => 1,
        'getactionname' => 1,
        'getcacheinstance' => 1,
        'setactionname' => 1,
        'on' => 1,
        'exceptapi' => 1,
        'doneapi' => 1,
    ];

    public static function fixActionParams($obj, $func, $params)
    {
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
                if ($arg['isArray'] && !is_array($tmp)) {
                    $tmp = [$tmp];   //参数要求为数组，把单个参数包装为数组
                }
                $tmp_args[$arg_name] = $tmp;
            } else {
                $tmp_args[$arg_name] = $arg['isOptional'] ? $arg['defaultValue'] : '';   //参数未给出时优先使用函数的默认参数，如果无默认参数这设置为空字符串
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
        $log_msg = "build API.js@{$cls}, method:" . json_encode($method_list);
        self::debug($log_msg, __METHOD__, __CLASS__, __LINE__);

        $debug = (defined('DEV_MODEL') && DEV_MODEL == 'DEBUG') ? 'true' : 'false';
        $js_str = <<<EOT
/*!
 * {$cls}.js
 * Auto Create By ApiHelper
 * build at {$date_str}
 */
(function (global, factory) {
	typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
	typeof define === 'function' && define.amd ? define(factory) :
	(global.Vue = factory());
}(this, (function () { 'use strict';

/*  */

function {$cls}Helper(){
    var _this = this;
    this.DEBUG = {$debug};
    var _log_func = (typeof console != "undefined" && typeof console.info == "function" && typeof console.warn == "function") ? {INFO: console.info.bind(console), ERROR: console.warn.bind(console)} : {};
    
    var _formatDate = function(){
        var now = new Date(new Date().getTime());
        var year = now.getFullYear();
        var month = now.getMonth()+1;
        var date = now.getDate();
        var hour = now.getHours();
        var minute = now.getMinutes();
        if(minute < 10){
            minute = '0' + minute.toString();
        } 
        var seconds = now.getSeconds();
        if(seconds < 10){
            seconds = '0' + seconds.toString();
        }
        return year+"-"+month+"-"+date+" "+hour+":"+minute+":"+seconds;
    };
    
    var _rfcApi = function(type, url, args, success, error, log){
        var start_time = new Date().getTime();
        if( typeof CSRF_TOKEN != "undefined" && CSRF_TOKEN ){
            args.csrf = CSRF_TOKEN;
        }
        $.ajax({
            type: type,
            url: url,
            data: args,
            dataType: 'json',
            success:
                function(data) {
                    var use_time = Math.round( (new Date().getTime() - start_time) );
                    if(data.errno == 0 || typeof data.error == "undefined" ){
                        log('INFO', use_time, args, data);
                        typeof(success) == 'function' && success(data);
                    } else {
                        log('ERROR', use_time, args, data);
                        typeof(error) == 'function' && error(data);
                    }
                }
        });
    };

EOT;

        foreach ($method_list as $key => $val) {
            $name = $val['name'];
            $doc_str = $dev_debug ? $val['doc'] : '';
            $args = json_encode(self::getExampleArgsByParameters($val['param']));
            $args_str = $dev_debug ? "this.{$name}_args = {$args};" : '';
            $func_item = <<<EOT

    {$doc_str}
    this.{$name} = function(args, success, error) {
        args = args || {};
        var log = function(tag, use_time, args, data){
            var f = _log_func[tag]; typeof args.csrf != "undefined" && delete args.csrf;
            _this.DEBUG && f && f(_formatDate(), '['+tag+'] {$cls}.{$name}('+use_time+'ms)', 'args:', args, 'data:', data);
        };
        return _rfcApi('POST', '/api/{$cls}/{$name}' ,args, success, error, log);
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
                $method_list[] = [
                    'name' => $val->getName(),
                    'doc' => $val->getDocComment(),
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