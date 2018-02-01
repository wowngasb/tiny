<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/12 0012
 * Time: 15:26
 */

namespace Tiny\Traits;

use Closure;
use phpFastCache\CacheManager;
use Tiny\Application;
use Tiny\Plugin\EmptyMock;

trait CacheTrait
{
    // 是否 优先使用 redis 进行缓存
    protected static $_cache_use_redis = true;
    private static $_cache_default_expires = 300;
    private static $_cache_prefix_key = 'Cache';
    private static $_cache_max_key_len = 128;

    private static $_mCacheManager = null;

    private static $_redis_instance = null;
    private static $_redis_instance_monk = null;


    ############################################################
    ########################## 对外方法 #######################
    ############################################################

    public static function _hashKey($args_input, $tag = "no_args")
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
        if (strlen($key_str) > self::$_cache_max_key_len) {
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

    /**
     * 使用redis缓存函数调用的结果 优先使用缓存中的数据
     * @param string $method 所在方法 方便检索
     * @param string $key 缓存 keys
     * @param callable $func 获取结果的调用 没有任何参数  需要有返回结果
     * @param callable $filter 判断结果是否可以缓存的调用 参数为 $func 的返回结果 返回值为bool
     * @param int | null $timeCache 允许的数据缓存时间 0表示返回函数结果并清空缓存  负数表示不执行调用只清空缓存  默认为300
     * @param string $prefix 缓存键 的 前缀
     * @param array | Closure $tags 标记数组
     * @param bool $is_log 是否显示日志
     * @return mixed
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     */
    public static function _cacheDataManager($method, $key, callable $func, callable $filter, $timeCache = null, $prefix = null, $tags = [], $is_log = false)
    {
        if (self::$_cache_use_redis) {
            return self::_cacheDataByRedis($method, $key, $func, $filter, $timeCache, $prefix, $tags, $is_log);
        } else {
            return self::_cacheDataByFastCache($method, $key, $func, $filter, $timeCache, $prefix, $tags, $is_log);
        }
    }

    /**
     * 使用redis缓存函数调用的结果 优先使用缓存中的数据
     * @param string $method 所在方法 方便检索
     * @param string $key 缓存 keys
     * @param string $prefix 缓存键 的 前缀
     * @param array | Closure $tags 标记数组
     * @param bool $is_log 是否显示日志
     */
    public static function _clearDataManager($method = '', $key = '', $prefix = null, $tags = [], $is_log = false)
    {
        if (self::$_cache_use_redis) {
            self::_clearDataByRedis($method, $key, $prefix, $tags, $is_log);
        } else {
            self::_clearDataByFastCache($method, $key, $prefix, $tags, $is_log);
        }
    }

    /**
     * 使用redis缓存函数调用的结果 优先使用缓存中的数据
     * @param string $method 所在方法 方便检索
     * @param array $keys 缓存 keys
     * @param int | null $timeCache 允许的数据缓存时间 0表示返回函数结果并清空缓存  负数表示不执行调用只清空缓存  默认为300
     * @param string $prefix 缓存键 的 前缀
     * @param bool $is_log 是否显示日志
     * @return array
     */
    public static function _mgetDataManager($method, array $keys, $timeCache = null, $prefix = null, $is_log = false)
    {
        if (self::$_cache_use_redis) {
            return self::_mgetDataByRedis($method, $keys, $timeCache, $prefix, $is_log);
        } else {
            return self::_mgetDataByFastCache($method, $keys, $timeCache, $prefix, $is_log);
        }
    }

    #################################################
    ###################  私有方法 ###################
    #################################################

    private static function _mgetDataByFastCache($method, array $keys, $timeCache = null, $prefix = null, $is_log = false)
    {
        if (empty($keys) || empty($method)) {
            error_log("call _mgetDataByFastCache with empty method or keys " . __METHOD__);
            return [];
        }
        $mCache = self::_getCacheInstance();
        if (empty($mCache)) {
            error_log(__METHOD__ . ' can not get mCache by _getCacheInstance ' . __METHOD__);
            return [];
        }

        $now = time();
        $timeCache = is_null($timeCache) ? self::$_cache_prefix_key : $timeCache;
        $timeCache = intval($timeCache);

        $rKeysMap = [];
        foreach ($keys as $d_key => $r_key) {
            $rKeysMap[$d_key] = self::_buildCacheKey($method, $r_key, $prefix);
        }

        if ($timeCache <= 0) {
            $mCache->deleteItems(array_values($rKeysMap));
            $is_log && self::_cacheDebug('mdel', $now, $method, join(',', $keys), $timeCache, $now);
            return [];
        }
        $list = $mCache->getItems(array_values($rKeysMap));
        $ret_map = [];
        $idx = 0;
        foreach ($keys as $d_key => $r_key) {
            $tmp_item = !empty($list[$idx]) ? $list[$idx] : null;
            $val_str = !empty($tmp_item) ? $tmp_item->get() : '';
            $val = !empty($val_str) ? static::_decodeResolver($val_str) : [];  //判断缓存有效期是否在要求之内
            $data = null;
            if (isset($val['data']) && isset($val['_update_']) && $now - $val['_update_'] < $timeCache) {
                $is_log && self::_cacheDebug('mhit', $now, $method, $r_key, $timeCache, $val['_update_']);
                $data = $val['data'];
            }
            $ret_map[$d_key] = $data;
            $idx += 1;
        }
        return $ret_map;
    }

    private static function _clearDataByFastCache($method = '', $key = '', $prefix = null, array $tags = [], $is_log = false)
    {
        $mCache = self::_getCacheInstance();
        if (empty($mCache)) {
            error_log(__METHOD__ . ' can not get mCache by _getCacheInstance ' . __METHOD__);
            return;
        }
        $now = time();
        if (!empty($method) || !empty($key)) {
            $rKey = self::_buildCacheKey($method, $key, $prefix);
            $mCache->deleteItem($rKey);
            $is_log && self::_cacheDebug('delete by key', $now, $method, $key, -1, $now, $tags);
        }
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $mCache->deleteItemsByTag($tag);
            }
            $is_log && self::_cacheDebug('delete by tag', $now, $method, $key, -1, $now, $tags);
        }
    }

    private static function _cacheDataByFastCache($method, $key, callable $func, callable $filter, $timeCache = null, $prefix = null, $tags = [], $is_log = false)
    {
        if (empty($key) || empty($_method)) {
            error_log("call _cacheDataByFastCache with empty method or key");
            return [];
        }

        $mCache = self::_getCacheInstance();
        if (empty($mCache)) {
            error_log(__METHOD__ . ' can not get mCache by _getCacheInstance!');
            return $func();
        }

        $now = time();
        $timeCache = is_null($timeCache) ? self::$_cache_default_expires : $timeCache;
        $timeCache = intval($timeCache);
        $rKey = self::_buildCacheKey($method, $key, $prefix);

        if ($timeCache <= 0) {
            $data = $timeCache == 0 ? $func() : [];
            self::_clearDataByFastCache($method, $key, $prefix, self::_buildTagsByData($tags, $data), $is_log);
            return $data;
        }

        $val_str = $mCache->getItem($rKey)->get();
        $val = !empty($val_str) ? static::_decodeResolver($val_str) : [];  //判断缓存有效期是否在要求之内  数据符合要求直接返回  不再执行 func
        if (isset($val['data']) && isset($val['_update_']) && $now - $val['_update_'] < $timeCache) {
            $is_log && self::_cacheDebug('hit', $now, $method, $key, $timeCache, $val['_update_'], $tags);
            return $val['data'];
        }

        $data = $func();
        $val = ['data' => $data, '_update_' => time()];
        $use_cache = $filter($val['data']);
        if (is_numeric($use_cache) && $use_cache > 0) {  //当 $filter 返回一个数字时  使用返回结果当作缓存时间
            $timeCache = $use_cache;
        }

        if ($use_cache) {   //需要缓存 且缓存世间大于0 保存数据并加上 tags
            $itemObj = $mCache->getItem($rKey)->set(static::_encodeResolver($val))->expiresAfter($timeCache);
            $tags = self::_buildTagsByData($tags, $data);
            !empty($tags) && $itemObj->setTags($tags);
            $mCache->save($itemObj);
            $is_log && self::_cacheDebug('cache', $now, $method, $key, $timeCache, $val['_update_'], $tags);
        } else {
            $is_log && self::_cacheDebug('skip', $now, $method, $key, $timeCache, $val['_update_'], $tags);
        }

        return $val['data'];
    }

    public static function _mgetDataByRedis($method, array $keys, $timeCache = null, $prefix = null, $is_log = false)
    {
        if (empty($keys) || empty($method)) {
            error_log("call _mgetDataByRedis with empty method or keys " . __METHOD__);
            return [];
        }
        $mRedis = self::_getRedisInstance();
        if (empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not get mRedis by _getRedisInstance ' . __METHOD__);
            return self::_mgetDataByFastCache($mRedis, $keys, $timeCache, $prefix, $is_log);
        }

        $now = time();
        $timeCache = is_null($timeCache) ? self::$_cache_prefix_key : $timeCache;
        $timeCache = intval($timeCache);

        $rKeysMap = [];
        foreach ($keys as $idx => $key) {
            $rKeysMap[$idx] = self::_buildCacheKey($method, $key, $prefix);
        }

        if ($timeCache <= 0) {
            $mRedis->del(array_values($rKeysMap));
            $is_log && self::_cacheDebug('mdel', $now, $method, join(',', $keys), $timeCache, $now);
            return [];
        }
        $list = $mRedis->mget(array_values($rKeysMap));
        $ret_map = [];
        $idx = 0;
        foreach ($keys as $jdx => $jkey) {
            $val_str = !empty($list[$idx]) ? $list[$idx] : '';
            $val = !empty($val_str) ? static::_decodeResolver($val_str) : [];  //判断缓存有效期是否在要求之内
            $data = null;
            if (isset($val['data']) && isset($val['_update_']) && $now - $val['_update_'] < $timeCache) {
                $is_log && self::_cacheDebug('mhit', $now, $method, $jkey, $timeCache, $val['_update_']);
                $data = $val['data'];
            }
            $ret_map[$jdx] = $data;
            $idx += 1;
        }
        return $ret_map;
    }

    private static function _clearDataByRedis($method = '', $key = '', $prefix = null, array $tags = [], $is_log = false)
    {
        $mRedis = self::_getRedisInstance();
        if (empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not get mRedis by _getRedisInstance' . __METHOD__);
            self::_clearDataByFastCache($method . $key, $prefix, $tags, $is_log);
            return;
        }
        $now = time();
        if (!empty($method) || !empty($key)) {
            $rKey = self::_buildCacheKey($method, $key, $prefix);
            $mRedis->del($rKey);
            $is_log && self::_cacheDebug('delete by key', $now, $method, $key, -1, $now, $tags);
        }
        if (!empty($tags)) {
            $prefix = self::_buildPreFix($prefix);
            foreach ($tags as $tag) {
                $tagKey = "{$prefix}_tags:{$tag}";
                $rKeyList = $mRedis->sMembers($tagKey);
                if (!empty($rKeyList)) {
                    foreach ($rKeyList as $rKey) {
                        $mRedis->del($rKey);
                        $mRedis->sRem($tagKey, $rKey);
                    }
                }
            }
            $is_log && self::_cacheDebug('delete by tag', $now, $method, $key, -1, $now, $tags);
        }
    }

    private static function _cacheDataByRedis($method, $key, callable $func, callable $filter, $timeCache = null, $prefix = null, $tags = [], $is_log = false)
    {
        if (empty($key) || empty($method)) {
            error_log("call _cacheDataByRedis with empty method or key " . __METHOD__);
            return [];
        }
        $mRedis = self::_getRedisInstance();
        if (empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not get mRedis by _cacheDataByRedis' . __METHOD__);
            return self::_cacheDataByFastCache($method, $key, $func, $filter, $timeCache, $is_log, $prefix, $tags);
        }

        $now = time();
        $timeCache = is_null($timeCache) ? self::$_cache_prefix_key : $timeCache;
        $timeCache = intval($timeCache);
        $rKey = self::_buildCacheKey($method, $key, $prefix);
        if ($timeCache <= 0) {
            $data = $timeCache == 0 ? $func() : [];
            self::_clearDataByRedis($method, $key, $prefix, self::_buildTagsByData($tags, $data), $is_log);
            return $data;
        }

        $val_str = $mRedis->get($rKey);
        $val = !empty($val_str) ? static::_decodeResolver($val_str) : [];  //判断缓存有效期是否在要求之内  数据符合要求直接返回  不再执行 func
        if (isset($val['data']) && isset($val['_update_']) && $now - $val['_update_'] < $timeCache) {
            $is_log && self::_cacheDebug('hit', $now, $method, $key, $timeCache, $val['_update_'], $tags);
            return $val['data'];
        }

        $data = $func();
        $val = ['data' => $data, '_update_' => time()];
        $use_cache = $filter($val['data']);
        if (is_numeric($use_cache) && $use_cache > 0) {  //当 $filter 返回一个数字时  使用返回结果当作缓存时间
            $timeCache = $use_cache;
        }

        if ($use_cache) {   //需要缓存 且缓存时间大于0 保存数据并加上 tags
            $mRedis->setex($rKey, $timeCache, static::_encodeResolver($val));
            $tags = self::_buildTagsByData($tags, $data);
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $mRedis->sAdd("{$prefix}_tags:{$tag}", "{$rKey}");
                }
            }
            $is_log && self::_cacheDebug("cache {$use_cache}", $now, $method, $key, $timeCache, $val['_update_'], $tags);
        } else {
            $is_log && self::_cacheDebug("skip {$use_cache}", $now, $method, $key, $timeCache, $val['_update_'], $tags);
        }

        return $val['data'];
    }


    ################################################
    ################## 可重写方法 #################
    ################################################

    protected static function _encodeResolver($val)
    {
        return json_encode($val);
    }

    protected static function _decodeResolver($str)
    {
        return json_decode($str, true);
    }

    protected static function _methodResolver($method)
    {
        return $method;
    }

    protected static function _preFixResolver($prefix)
    {
        return $prefix;
    }

    ####################################################
    ##################### 辅助方法 ####################
    ####################################################

    private static function _buildCacheKey($method, $key, $prefix = null)
    {
        $prefix = self::_buildPreFix($prefix);
        $method = self::_buildMethod($method);
        $rKey = !empty($prefix) ? "{$prefix}:{$method}:{$key}" : "{$method}:{$key}";
        return $rKey;
    }

    private static function _buildTagsByData($tags = [], $data = null)
    {
        if (!empty($data) && !empty($tags) && is_callable($tags)) {
            return call_user_func_array($tags, [$data]);
        }
        return $tags;
    }

    private static function _buildMethod($method)
    {
        $method = static::_methodResolver($method);
        $method = str_replace('::', '.', $method);
        return trim($method);
    }

    private static function _buildPreFix($prefix = null)
    {
        if (is_null($prefix)) {
            static::_preFixResolver($prefix);
        }
        if (is_null($prefix)) {
            $prefix = self::$_cache_prefix_key;
        }
        return trim($prefix);
    }

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

    private static function _cacheDebug($action, $now, $method, $key, $timeCache, $update, $tags = [])
    {
        $log_msg = "{$action} now:{$now}, method:{$method}, key:{$key}, timeCache:{$timeCache}, _update_:{$update}";
        if (!empty($tags) && is_array($tags)) {
            $log_msg .= ", tags:[" . join(',', $tags) . ']';
        }
        LogTrait::debug($log_msg, __METHOD__, __CLASS__, __LINE__);
    }

}