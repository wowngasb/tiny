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
use Tiny\Application;
use Tiny\Event\CacheEvent;

class CacheConfig extends AbstractClass
{
    // 是否 优先使用 redis 进行缓存
    private $_cache_use_redis = true;
    // 是否启用 静态缓存
    private $_enable_static_cache = false;
    private $_enable_yac_cache = false;

    private $_encodeResolver = null;
    private $_decodeResolver = null;
    private $_methodResolver = null;
    private $_preFixResolver = null;
    private $_redisConfigResolver = null;

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

    public function isEnableStaticCache($prefix = null)
    {
        $cfg = $this->redisConfig($prefix);
        return isset($cfg['enable_static_cache']) ? !empty($cfg['enable_static_cache']) : $this->_enable_static_cache;
    }

    public function isEnableYacCache($prefix = null)
    {
        $cfg = $this->redisConfig($prefix);
        return isset($cfg['enable_yac_cache']) ? !empty($cfg['enable_yac_cache']) : $this->_enable_yac_cache;
    }

    public function isCacheUseRedis($prefix = null)
    {
        $cfg = $this->redisConfig($prefix);
        return isset($cfg['cache_use_redis']) ? !empty($cfg['cache_use_redis']) : $this->_cache_use_redis;
    }


    /**
     * @param bool $use_redis
     * @param bool $enable_static
     */
    public function setBaseConfig($use_redis, $enable_static = false, $enable_yac = false)
    {
        $this->_cache_use_redis = !empty($use_redis);
        $this->_enable_static_cache = !empty($enable_static);
        $this->_enable_yac_cache = !empty($enable_yac);
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

    public function setRedisConfigResolver(Closure $resolver)
    {
        $this->_redisConfigResolver = $resolver;
    }

    public function encodeResolver($prefix, $val)
    {
        if (!empty($this->_encodeResolver)) {
            $prefix = $this->preFixResolver($prefix);
            return call_user_func_array($this->_encodeResolver, [$prefix, $val]);
        }
        return json_encode($val);
    }

    public function decodeResolver($prefix, $str)
    {
        if (!empty($this->_decodeResolver)) {
            $prefix = $this->preFixResolver($prefix);
            return call_user_func_array($this->_decodeResolver, [$prefix, $str]);
        }
        return json_decode($str, true);
    }

    public function methodResolver($prefix, $method)
    {
        if (!empty($this->_methodResolver)) {
            $prefix = $this->preFixResolver($prefix);
            return call_user_func_array($this->_methodResolver, [$prefix, $method]);
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

    public function redisConfig($prefix)
    {
        if (!empty($this->_redisConfigResolver)) {
            $prefix = $this->preFixResolver($prefix);
            $cfg = call_user_func_array($this->_redisConfigResolver, [$prefix]);
            return !empty($cfg) ? $cfg : Application::config('ENV_REDIS');
        }
        return Application::config('ENV_REDIS');
    }

    public static function doneCacheAction($action, $now, $method, $key, $timeCache, $update, $tags = [], $useStatic = false, $useYac = false, $bytes = 0)
    {
        self::fire(new CacheEvent($action, $now, $method, $key, $timeCache, $update, $tags, $useStatic, $useYac, $bytes));
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