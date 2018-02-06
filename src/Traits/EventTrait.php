<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/30 0030
 * Time: 1:56
 */

namespace Tiny\Traits;


use Tiny\Exception\AppStartUpError;
use Tiny\Interfaces\EventInterface;

trait EventTrait
{

    protected static $_event_map = [];  // 注册事件列表

    /**
     * 判断一个事件是否允许注册 默认 不允许任何类型
     * @param string $type
     * @return bool
     */
    protected static function isAllowedEvent($type)
    {
        false && func_get_args();
        return false;
    }

    /*
     *  注册回调函数
     * @param string $event
     * @param callable $callback
     */
    public static function on($type, callable $callback)
    {
        if (!static::isAllowedEvent($type)) {
            throw new AppStartUpError("event:{$type} not support");
        }
        if (!isset(static::$_event_map[$type])) {
            static::$_event_map[$type] = [];
        }
        static::$_event_map[$type][] = $callback;
    }

    /**
     * 触发事件  依次调用注册的回调
     * @param  EventInterface $event 事件名称
     * @throws AppStartUpError
     */
    protected static function fire(EventInterface $event)
    {
        $type = $event->getType();
        if (!static::isAllowedEvent($type)) {
            throw new AppStartUpError("event:{$type} not support");
        }
        $callback_list = isset(static::$_event_map[$type]) ? static::$_event_map[$type] : [];
        foreach ($callback_list as $idx => $func) {
            call_user_func_array($func, [$event]);
        }
    }

}