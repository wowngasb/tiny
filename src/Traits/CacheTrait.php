<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/12 0012
 * Time: 15:26
 */

namespace Tiny\Traits;

use phpFastCache\CacheManager;
use Tiny\Application;
use Tiny\Plugin\EmptyMock;

trait CacheTrait
{
    private static $_use_redis = true;

    private static $_redis_default_expires = 300;
    private static $_redis_prefix_cache = 'BMCache';
    private static $_max_key_len = 128;

    private static $_mCacheManager = null;

    private static $_redis_instance = null;
    private static $_redis_instance_monk = null;

    private static function _fix_cache_key($data)
    {
        $type = gettype($data);
        switch ($type) {
            case 'NULL':
                return 'null';
            case 'boolean':
                return ($data ? 'true' : 'false');
            case 'integer':
            case 'double':
            case 'float':
            case 'string':
                return $data;
            case 'object':
                $data = get_object_vars($data);
                return self::_fix_cache_key($data);
            case 'array':
                $output_index_count = 0;
                $output_indexed = [];
                $output_associative = [];
                foreach ($data as $key => $value) {
                    $output_indexed[] = self::_fix_cache_key($value);
                    $output_associative[] = self::_fix_cache_key($key) . ':' . self::_fix_cache_key($value);
                    if ($output_index_count !== null && $output_index_count++ !== $key) {
                        $output_index_count = null;
                    }
                }
                if ($output_index_count !== null) {
                    return '[' . implode(',', $output_indexed) . ']';
                } else {
                    return '{' . implode(',', $output_associative) . '}';
                }
            default:
                return ''; // Not supported
        }
    }

    protected static function _buildHashKey($args_input, $tag = "no_args")
    {
        $args_list = [];
        foreach ($args_input as $key => $val) {
            $key = trim($key);
            $val = self::_fix_cache_key($val);
            if (!empty($key)) {
                $args_list[] = "{$key}=" . urlencode($val);
            }
        }
        $key_str = !empty($args_list) ? join($args_list, '&') : $tag;
        if (strlen($key_str) > self::$_max_key_len) {
            $key_str = substr($key_str, 0, 32) . "#" . md5($key_str);
        }
        return $key_str;
    }

    /**
     * @return null|\phpFastCache\Cache\ExtendedCacheItemPoolInterface
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     */
    public static function _getCacheInstance()
    {
        if (is_null(self::$_mCacheManager)) {
            $env_cache = Application::config('ENV_CACHE');
            $type = !empty($env_cache['type']) ? $env_cache['type'] : 'file';
            $config = !empty($env_cache['config']) ? $env_cache['config'] : [];
            self::$_mCacheManager = CacheManager::getInstance($type, $config);
        }
        return self::$_mCacheManager;
    }


    /**
     * @return \Redis
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     */
    public static function _getRedisInstance()
    {
        if (!empty(self::$_redis_instance)) {
            return self::$_redis_instance;
        }

        try {
            $redis = self::_get_redis();
            if (!empty($redis)) {
                self::$_redis_instance = $redis;
                return $redis;
            }
        } catch (\Exception $e) {
            //忽略异常 尝试返回一个空的 mock 对象
            error_log("create redis with error:" . $e->getMessage());
        }

        if (empty(self::$_redis_instance_monk)) {
            /** @var \Redis $redis */
            $redis = new EmptyMock();
            self::$_redis_instance_monk = $redis;
        }
        return self::$_redis_instance_monk;
    }

    private static function _get_redis()
    {
        if (!class_exists('Redis')) {
            return null;
        }
        $redis = new \Redis();
        $host = Application::config('ENV_REDIS.host', '127.0.0.1');
        $port = intval(Application::config('ENV_REDIS.port', 6379));
        $password = Application::config('ENV_REDIS.password', '');
        $database = intval(Application::config('ENV_REDIS.database', 0));

        if (!$redis->connect($host, $port)) {
            return null;
        }
        if (!empty($password)) {
            return $redis->auth($password) ? $redis : null;
        }
        if ($database > 0) {
            return $redis->select($database) ? $redis : null;
        }
        return $redis;
    }

    /**
     * 使用redis缓存函数调用的结果 优先使用缓存中的数据
     * @param string $method 所在方法 方便检索
     * @param string $key redis 缓存tag 表示分类
     * @param callable $func 获取结果的调用 没有任何参数  需要有返回结果
     * @param callable $filter 判断结果是否可以缓存的调用 参数为 $func 的返回结果 返回值为bool
     * @param int $timeCache 允许的数据缓存时间 0表示返回函数结果并清空缓存  负数表示不执行调用只清空缓存  默认为300
     * @param bool $is_log 是否显示日志
     * @param string $prefix 缓存键 的 前缀
     * @param array $tags 标记数组
     * @return mixed
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     */
    public static function _cacheDataManager($method, $key, callable $func, callable $filter, $timeCache = null, $is_log = false, $prefix = null, array $tags = [])
    {
        if (self::$_use_redis) {
            return self::_cacheDataByRedis($method, $key, $func, $filter, $timeCache, $is_log, $prefix, $tags);
        } else {
            return self::_cacheDataByFastCache($method, $key, $func, $filter, $timeCache, $is_log, $prefix, $tags);
        }
    }


    public static function _cacheDataByFastCache($method, $key, callable $func, callable $filter, $timeCache = null, $is_log = false, $prefix = null, array $tags = [])
    {
        $mCache = self::_getCacheInstance();
        if (empty($key) || empty($method) || empty($mCache)) {
            return $func();
        }

        $prefix = is_null($prefix) ? self::$_redis_prefix_cache : $prefix;
        $timeCache = is_null($timeCache) ? self::$_redis_default_expires : $timeCache;
        $method = str_replace('::', '.', $method);
        $now = time();
        $timeCache = intval($timeCache);
        $rKey = !empty($prefix) ? "{$prefix}:{$method}?{$key}" : "{$method}?{$key}";
        if ($timeCache <= 0) {
            $mCache->deleteItem($rKey);
            $is_log && self::_redisDebug('delete', $now, $method, $key, $timeCache, $now, $tags);
            return $timeCache == 0 ? $func() : [];
        }

        $val = $mCache->getItem($rKey)->get() ?: [];  //判断缓存有效期是否在要求之内  数据符合要求直接返回  不再执行 func
        if (isset($val['data']) && isset($val['_update_']) && $now - $val['_update_'] < $timeCache) {
            $is_log && self::_redisDebug('hit', $now, $method, $key, $timeCache, $val['_update_'], $tags);
            return $val['data'];
        }

        $val = ['data' => $func(), '_update_' => time()];
        $use_cache = $filter($val['data']);
        if (is_numeric($use_cache) && $use_cache > 0) {  //当 $filter 返回一个数字时  使用返回结果当作缓存时间
            $timeCache = $use_cache;
        }

        if ($use_cache) {   //需要缓存 且缓存世间大于0 保存数据并加上 tags
            $itemObj = $mCache->getItem($rKey)->set($val)->expiresAfter($timeCache);
            !empty($tags) && $itemObj->setTags($tags);
            $mCache->save($itemObj);
            $is_log && self::_redisDebug('cache', $now, $method, $key, $timeCache, $val['_update_'], $tags);
        } else {
            $is_log && self::_redisDebug('skip', $now, $method, $key, $timeCache, $val['_update_'], $tags);
        }

        return $val['data'];
    }

    protected static function _cacheDataByRedis($method, $key, callable $func, callable $filter, $timeCache = null, $is_log = false, $prefix = null, array $tags = [])
    {
        $mRedis = self::_getRedisInstance();
        if (empty($key) || empty($method) || empty($mRedis)) {
            error_log(__METHOD__ . ' can not get mRedis!');
            return $func();
        }
        $_prefix = is_null($prefix) ? self::$_redis_prefix_cache : $prefix;
        $timeCache = is_null($timeCache) ? self::$_redis_prefix_cache : $timeCache;
        $method = str_replace('::', ':', $method);
        $now = time();
        $timeCache = intval($timeCache);
        $rKey = !empty($prefix) ? "{$_prefix}:{$method}:{$key}" : "{$method}:{$key}";
        if ($timeCache <= 0) {
            $mRedis->del($rKey);
            $is_log && self::_redisDebug('delete', $now, $method, $key, $timeCache, $now, $tags);
            return $timeCache == 0 ? $func() : [];
        }

        $json_str = $mRedis->get($rKey);
        $val = !empty($json_str) ? json_decode($json_str, true) : [];  //判断缓存有效期是否在要求之内  数据符合要求直接返回  不再执行 func
        if (isset($val['data']) && isset($val['_update_']) && $now - $val['_update_'] < $timeCache) {
            $is_log && self::_redisDebug('hit', $now, $method, $key, $timeCache, $val['_update_'], $tags);
            return $val['data'];
        }

        $val = ['data' => $func(), '_update_' => time()];
        $use_cache = $filter($val['data']);
        if (is_numeric($use_cache) && $use_cache > 0) {  //当 $filter 返回一个数字时  使用返回结果当作缓存时间
            $timeCache = $use_cache;
        }

        if ($use_cache) {   //需要缓存 且缓存时间大于0 保存数据并加上 tags
            $mRedis->setex($rKey, $timeCache, json_encode($val));
            $is_log && self::_redisDebug("cache {$use_cache}", $now, $method, $key, $timeCache, $val['_update_'], $tags);
        } else {
            $is_log && self::_redisDebug("skip {$use_cache}", $now, $method, $key, $timeCache, $val['_update_'], $tags);
        }

        return $val['data'];
    }

    private static function _redisDebug($action, $now, $method, $key, $timeCache, $update, array $tags = [])
    {
        $log_msg = "{$action} now:{$now}, method:{$method}, key:{$key}, timeCache:{$timeCache}, _update_:{$update}";
        if (!empty($tags)) {
            $log_msg .= ", tags:[" . join(',', $tags) . ']';
        }
        LogTrait::debug($log_msg, __METHOD__, __CLASS__, __LINE__);
    }

}