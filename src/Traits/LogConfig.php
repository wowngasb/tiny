<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/2/2 0002
 * Time: 9:46
 */

namespace Tiny\Traits;


use Tiny\Abstracts\AbstractClass;
use Tiny\Event\LogEvent;

class LogConfig extends AbstractClass
{


    public static function doneLogAction($type, $msg, $method, $class, $line_no)
    {
        self::fire(new LogEvent(strtolower($type), $msg, $method, $class, $line_no));
    }

    ###############################################################
    ############## 重写 EventTrait::isAllowedEvent ################
    ###############################################################

    /**
     *  注册回调函数  回调参数为 callback(\Tiny\Event\LogEvent $event)
     * @param string $type
     * @return bool
     */
    public static function isAllowedEvent($type)
    {
        static $allow_map = [
            'debug' => 1,
            'info' => 1,
            'warn' => 1,
            'error' => 1,
            'fatal' => 1,
        ];
        return !empty($allow_map[$type]);
    }

}