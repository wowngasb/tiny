<?php

namespace Tiny\Tests;

use PHPUnit_Framework_TestCase;

/**
 * BaseNothingTest
 */
class BaseNothingTest extends PHPUnit_Framework_TestCase
{

    public static $_class = __CLASS__;

    public function testNothing()
    {
    }

    protected static function _buildMsg($func, $args, $ret = null)
    {
        if (is_array($args)) {
            $arg_str = [];
            foreach ($args as $arg) {
                $arg_str[] = json_encode($arg);
            }
            $args_str = join(', ', $arg_str);
        } else {
            $args_str = json_encode($args);
        }
        $ret_str = json_encode($ret);

        $msg = static::$_class . "::{$func}({$args_str})={$ret_str}";
        return $msg;
    }

    protected static function _buildFunc($method)
    {
        $tmp_list = explode('::', $method);
        $method = count($tmp_list) == 2 ? $tmp_list[1] : $method;
        if (substr($method, 0, 5) == 'test_') {
            $method = substr($method, 5);
        }
        return $method;
    }
}