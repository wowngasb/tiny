<?php

namespace Tiny\Plugin;

use Exception;
use Memcache;
use Tiny\Application;

class MemcacheHelper
{
    private static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            try {
                self::$instance = new Memcache();
                $host = Application::app()->getEnv('ENV_MEMCACHE_HOST', 'localhost');
                $port = Application::app()->getEnv('ENV_MEMCACHE_PORT', 11211);
                if (!self::$instance->connect($host, $port)) {
                    throw new Exception('connect memcache server failed!');
                }
            } catch (Exception $e) {
                return false;
            }
        }
        return self::$instance;
    }

    private function __construct()
    {
    }
}