<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/2/2 0002
 * Time: 9:49
 */

namespace Tiny\Traits;


trait MapInstanceTraits
{

    private static $_instance_map = [];

    final public static function _delInstanceByKey($key)
    {
        unset(self::$_instance_map[$key]);
    }

    /**
     * 简单容器封装  可以根据 key 获取实例  如果不存在 将自动创建实例
     * @param string $key
     * @param callable | null $resolver
     * @return mixed | null
     */
    final public static function _getInstanceByKey($key, callable $resolver = null)
    {
        $key = trim($key);
        if (empty($key) || (empty(self::$_instance_map[$key]) && empty($resolver))) {
            return null;
        }

        $_instance = !empty(self::$_instance_map[$key]) ? self::$_instance_map[$key] : call_user_func_array($resolver, []);
        if (!empty($_instance)) {
            self::$_instance_map[$key] = $_instance;
        }
        return $_instance;
    }

}