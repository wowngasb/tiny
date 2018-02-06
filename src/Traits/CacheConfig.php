<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/2/2 0002
 * Time: 9:46
 */

namespace Tiny\Traits;


use Closure;
use Tiny\Abstracts\AbstractClass;
use Tiny\Event\CacheEvent;

class CacheConfig extends AbstractClass
{
    // 是否 优先使用 redis 进行缓存
    private $_cache_use_redis = true;
    // 是否启用 静态缓存
    private $_enable_static_cache = false;

    private $_encodeResolver = null;
    private $_decodeResolver = null;
    private $_methodResolver = null;
    private $_preFixResolver = null;

    use MapInstanceTraits;

    public static function setConfig(Closure $closure, $key = '__global__')
    {
        self::_delInstanceByKey($key);
        self::_getInstanceByKey($key, $closure);
    }

    public static function loadConfig($key = '__global__')
    {
        $tmp = self::_getInstanceByKey($key, null);
        if (empty($tmp) && $key != '__global__') {
            $tmp = self::_getInstanceByKey('__global__', null);
        }
        if (empty($tmp)) {
            $tmp = new static();
        }
        return $tmp;
    }

    public function isEnableStaticCache()
    {
        return $this->_enable_static_cache;
    }

    public function isCacheUseRedis()
    {
        return $this->_cache_use_redis;
    }


    /**
     * @param bool $use_redis
     * @param bool $enable_static
     */
    public function setBaseConfig($use_redis, $enable_static)
    {
        $this->_cache_use_redis = !empty($use_redis);
        $this->_enable_static_cache = !empty($enable_static);
    }

    /**
     * @param Closure $resolver
     */
    public function setEncodeResolver(Closure $resolver)
    {
        $this->_encodeResolver = $resolver;
    }

    /**
     * @param Closure $resolver
     */
    public function setDecodeResolver(Closure $resolver)
    {
        $this->_decodeResolver = $resolver;
    }

    /**
     * @param Closure $resolver
     */
    public function setMethodResolver(Closure $resolver)
    {
        $this->_methodResolver = $resolver;
    }

    /**
     * @param Closure $resolver
     */
    public function setPreFixResolver(Closure $resolver)
    {
        $this->_preFixResolver = $resolver;
    }

    public function encodeResolver($val)
    {
        if (!empty($this->_encodeResolver)) {
            return call_user_func_array($this->_encodeResolver, [$val]);
        }
        return json_encode($val);
    }

    public function decodeResolver($str)
    {
        if (!empty($this->_decodeResolver)) {
            return call_user_func_array($this->_decodeResolver, [$str]);
        }
        return json_decode($str, true);
    }

    public function methodResolver($method)
    {
        if (!empty($this->_methodResolver)) {
            return call_user_func_array($this->_methodResolver, [$method]);
        }
        return $method;
    }

    public function preFixResolver($prefix)
    {
        if (!empty($this->_preFixResolver)) {
            return call_user_func_array($this->_preFixResolver, [$prefix]);
        }
        return $prefix;
    }

    public static function doneCacheAction($action, $now, $method, $key, $timeCache, $update, $tags = [], $useStatic = false)
    {
        self::fire(new CacheEvent($action, $now, $method, $key, $timeCache, $update, $tags, $useStatic));
    }

    ###############################################################
    ############## 重写 EventTrait::isAllowedEvent ################
    ###############################################################

    /**
     *  注册回调函数  回调参数为 callback(\Tiny\Event\CacheEvent $event)
     * @param string $type
     * @return bool
     */
    public static function isAllowedEvent($type)
    {
        static $allow_map = [
            'mdel' => 1,
            'mhit' => 1,
            'delkey' => 1,
            'deltag' => 1,
            'hit' => 1,
            'cache' => 1,
            'skip' => 1,
        ];
        return !empty($allow_map[$type]);
    }

}