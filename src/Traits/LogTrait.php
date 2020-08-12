<?php

namespace Tiny\Traits;


use app\App;
use Tiny\Plugin\LogHelper;

trait LogTrait
{
    public static function log($type, $msg, $method = '', $class = 'sys_log', $line_no = 0)
    {
        if (App::dev()) {
            LogConfig::doneLogAction($type, $msg, $method, $class, $line_no);
        }

        $log = LogHelper::create(str_replace('\\', '_', $class));
        if ($line_no > 0) {
            $log_msg = !empty($method) ? "{$method}[{$line_no}] {$msg}" : $msg;
        } else {
            $log_msg = !empty($method) ? "{$method} {$msg}" : $msg;
        }
        return $log->writeLog($log_msg, $type);
    }

    public static function debug($msg, $method = '', $class = 'sys_log', $line_no = 0)
    {
        return static::log('DEBUG', $msg, $method, $class, $line_no);
    }

    public static function info($msg, $method = '', $class = 'sys_log', $line_no = 0)
    {
        return static::log('INFO', $msg, $method, $class, $line_no);
    }

    public static function warn($msg, $method = '', $class = 'sys_log', $line_no = 0)
    {
        return static::log('WARN', $msg, $method, $class, $line_no);
    }

    public static function error($msg, $method = '', $class = 'sys_log', $line_no = 0)
    {
        return static::log('ERROR', $msg, $method, $class, $line_no);
    }

    public static function fatal($msg, $method = '', $class = 'sys_log', $line_no = 0)
    {
        return static::log('FATAL', $msg, $method, $class, $line_no);
    }

    public static function debugResult($result, $method = '', $class = 'sys_log', $line_no = 0, $max_items = 10, $max_chars = 50)
    {
        $tmp_msg = self::_mixed2msg($result, $max_items, $max_chars);  //简化结果显示 隐藏过多的条目
        $log_msg = "FuncResult:{$tmp_msg}";
        static::debug($log_msg, $method, $class . '_', $line_no);
    }

    public static function funcGetArgs(array $args = [], $method = '')
    {
        if (!empty($method) && stripos($method, '::') <= 0) {
            return [];
        }
        try {
            $tmp = explode('::', $method);
            $reflection = new \ReflectionMethod($tmp[0], $tmp[1]);
            $param = $reflection->getParameters();
            $tmp_args = [];
            foreach ($param as $arg) {
                $tmp_args[$arg->name] = array_shift($args);
            }
            $args = $tmp_args;
        } catch (\Exception $e) {
            error_log("debugArgs Exception target:{$method}, error:" . $e->getMessage());
        }
        return $args;
    }

    protected static function _getMethodArgs(array $args, $method = '')
    {
        $func_args = [];
        try {
            $tmp = explode('::', $method);
            $reflection = new \ReflectionMethod($tmp[0], $tmp[1]);
            $param = $reflection->getParameters();

            foreach ($param as $arg) {
                $func_args[$arg->name] = array_shift($args);
            }
        } catch (\Exception $e) {
            error_log("debugArgs Exception target:{$method}, error:" . $e->getMessage());
        }
        return $func_args;
    }

    public static function debugArgs(array $args, $method = '', $class = 'sys_log', $line_no = 0, $max_items = 10, $max_chars = 50)
    {
        if (!empty($method)) {
            $args = self::_getMethodArgs($args, $method);
        }
        $log_msg = "FuncArgs:" . self::_mixed2msg($args, $max_items, $max_chars);
        static::debug($log_msg, $method, $class . '_', $line_no);
    }

    public static function _mixed2msg($var, $max_items = 10, $max_chars = 50, $indent = 0)
    {
        $tab = str_repeat("  ", $indent + 1);
        $tab_pre = str_repeat("  ", $indent);
        switch (gettype($var)) {
            case 'boolean':
                return $var ? 'true' : 'false';
            case 'integer':
            case 'double':
                return $var;
            case 'resource':
            case 'string':
                $var = str_replace(["\r", "\n", "<", ">", "&"], ['\r', '\n', '\x3c', '\x3e', '\x26'], addslashes($var));
                $total = strlen($var);
                if (strlen($var) > $max_chars) {
                    $var = mb_substr($var, 0, $max_chars) . "...<{$total} chars>..." . mb_substr($var, -7);
                }
                return "\"{$var}\"";
            case 'array':
            case 'object':
                if (is_array($var) && (empty ($var) || array_keys($var) === range(0, sizeof($var) - 1))) {
                    $total = count($var);
                    $output = [];
                    $item_idx = 0;
                    foreach ($var as $v) {
                        if ($item_idx > $max_items) {
                            $output[] = "{$tab}\"...total<{$total}>items...\"";
                            break;
                        }
                        $output[] = $tab . self::_mixed2msg($v, $max_items, $max_chars, $indent + 1);
                        $item_idx += 1;
                    }
                    return "[\n" . implode(",  \n", $output) . "\n{$tab_pre}]";
                }
                $total = 0;
                $output = [];
                $item_idx = 0;
                foreach ($var as $k => $v) {
                    $total += 1;
                    if ($item_idx > $max_items) {
                        $output[] = "{$tab}\"...total<{$total}>keys...\": \"....items..\"";
                        break;
                    }
                    $output[] = $tab . strval($k) . ': ' . self::_mixed2msg($v, $max_items, $max_chars, $indent + 1);
                    $item_idx += 1;
                }
                return "{\n" . implode(",  \n", $output) . "\n{$tab_pre}}";
            default:
                return 'null';
        }
    }

}