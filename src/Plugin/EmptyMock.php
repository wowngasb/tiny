<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/10/24
 * Time: 23:22
 */

namespace Tiny\Plugin;


/**
 * Class EmptyMock
 * 空的类型 支持 魔术方法 调用 始终返回 null
 * @package Tiny\Plugin
 */
class EmptyMock
{

    public function __call($name, $arguments)
    {
        return null;
    }

    public static function __callStatic($name, $arguments)
    {
        return null;
    }

    public function __get($name)
    {
        return null;
    }

}