<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/30 0030
 * Time: 1:56
 */

namespace Tiny\Traits;


use Tiny\Exception\AppStartUpError;

trait EventTrait
{

    protected static $_event_map = [];  // 注册事件列表

    /**
     * 判断一个事件是否允许注册
     * @param string $event
     * @return bool
     */
    protected static function isAllowedEvent($event)
    {
        false && func_get_args();
        return false;
    }

    /*
     *  注册回调函数
     * @param string $event
     * @param callable $callback
     */
    public static function on($event, callable $callback)
    {
        if (!static::isAllowedEvent($event)) {
            throw new AppStartUpError("event:{$event} not support");
        }
        if (!isset(self::$_event_map[$event])) {
            self::$_event_map[$event] = [];
        }
        self::$_event_map[$event][] = $callback;
    }

    /**
     * 触发事件  依次调用注册的回调
     * @param  string $event 事件名称
     * @param array $args 调用触发回调的参数
     * @throws AppStartUpError
     */
    protected static function fire($event, array $args)
    {
        if (!static::isAllowedEvent($event)) {
            throw new AppStartUpError("event:{$event} not support");
        }
        $callback_list = isset(self::$_event_map[$event]) ? self::$_event_map[$event] : [];
        foreach ($callback_list as $idx => $val) {
            call_user_func_array($val, $args);
        }
    }

}