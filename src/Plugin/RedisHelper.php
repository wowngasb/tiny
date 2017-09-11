<?php

namespace Tiny\Plugin;

use Exception;
use Redis;
use Tiny\Application;

function _redis_monk()
{
    if (!class_exists('EmptyRedis')) {
        class EmptyRedis
        {
            public function __call($name, $arguments)
            {
                return null;
            }
        }
    }
    return new EmptyRedis();
}

class RedisHelper
{

    private static $instance = null;
    private static $instance_monk = null;

    public static function getInstance()
    {
        if (!empty(self::$instance)) {
            return self::$instance;
        }
        try {
            $redis = self::get_redis();
            if (!empty($redis)) {
                self::$instance = $redis;
                return $redis;
            }
        } catch (Exception $e) {
            //忽略异常给出一个空的模拟redis类
        }
        return self::get_redis_monk();
    }

    private function __construct()
    {
    }

    private static function get_redis_monk()
    {
        if (empty(self::$instance_monk)) {
            $redis = _redis_monk();
            self::$instance_monk = $redis;
        }
        return self::$instance_monk;
    }

    private static function get_redis()
    {
        if (!class_exists('Redis')) {
            return null;
        }
        $redis = new Redis();
        $host = Application::app()->getEnv('ENV_REDIS_HOST', 'localhost');
        $port = Application::app()->getEnv('ENV_REDIS_PORT', 6379);
        $pass = Application::app()->getEnv('ENV_REDIS_PASS', '');
        if (!$redis->connect($host, $port)) {
            return null;
        }
        if (!empty($pass)) {
            return $redis->auth($pass) ? $redis : null;
        }
        return $redis;
    }
}