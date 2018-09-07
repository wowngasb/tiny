<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/3/5 0005
 * Time: 15:08
 */

namespace Tiny\Traits;


use Tiny\Exception\AppStartUpError;

trait DeferredGetBuffer
{
    protected static $_deferred_cache_id_map = [];

    protected static $_deferred_cache_item_map = [];

    /**
     * @param int $id
     * @param string $group
     */
    final public static function addDeferred($id, $group = 'default')
    {
        if (empty($id)) {
            return;
        }
        static::$_deferred_cache_id_map[$id] = $group;
    }

    /**
     * @param mixed $context
     * @param int $id
     * @param string $group
     * @return mixed
     */
    public static function getDeferred($context, $id, $group = 'default')
    {
        false && func_get_args();

        $id_key = "{$id}_{$group}";
        if (!isset(static::$_deferred_cache_item_map[$id_key])) {
            static::$_deferred_cache_item_map[$id_key] = static::loadItemById($id, $group);
        }
        return static::$_deferred_cache_item_map[$id_key];
    }

    /**
     * 辅助函数  根据 分组 group 来获取 未取值的 id 列表
     * @param string $group
     * @return array
     */
    final protected static function listUnCachedByGroup($group = 'default')
    {
        $ret = [];
        foreach (self::$_deferred_cache_id_map as $id => $g) {
            if ($g == $group && !isset(self::$_deferred_cache_item_map["{$id}_{$g}"])) {
                $ret[$id] = 1;
            }
        }
        return array_keys($ret);
    }

    /**
     * @param int $key_id
     * @param mixed $item
     */
    final protected static function setCacheItem($key_id, $item)
    {
        self::$_deferred_cache_item_map[$key_id] = $item;
    }

    /**
     * @throws AppStartUpError
     */
    public static function loadBuffered()
    {
        throw new AppStartUpError("must overwrite method DeferredGetBuffer::loadBuffered() :array");
    }

    /**
     * @param int $id
     * @param string $group
     * @return mixed
     * @throws AppStartUpError
     */
    protected static function loadItemById($id, $group = 'default')
    {
        false && func_get_args();

        throw new AppStartUpError("must overwrite method DeferredGetBuffer::loadItemById(\$id, \$group = 'default') :mixed");
    }

}