<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/12 0012
 * Time: 15:26
 */

namespace Tiny\Traits;

use app\App;
use Closure;
use phpFastCache\CacheManager;
use Tiny\Application;
use Tiny\Plugin\EmptyMock;

trait CacheTrait
{
    private static $_static_cache_map = [];
    private static $_static_tags_map = [];

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
        if (self::_isCacheUseRedis($prefix)) {
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
        if (self::_isCacheUseRedis($prefix)) {
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
        if (self::_isCacheUseRedis($prefix)) {
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
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);

        $rKeysMap = [];
        foreach ($keys as $data_key => $origin_key) {
            $rKeysMap[$data_key] = self::_buildCacheKey($method, $origin_key, $prefix);
        }

        if ($timeCache <= 0) {
            $mCache->deleteItems(array_values($rKeysMap));
            if ($isEnableStaticCache) {
                foreach ($rKeysMap as $del_rKey) {
                    unset(self::$_static_cache_map[$del_rKey]);
                }
            }
            self::_cacheDebug('mdel', $now, $method, join(',', $keys), $timeCache, $now, [], $isEnableStaticCache, $is_log);
            return [];
        }

        $cache_map = [];
        if ($isEnableStaticCache) {  // 如果启用了静态缓存  优先使用类中的缓存
            foreach ($keys as $static_key => $_origin_key) {
                $s_rKey = $rKeysMap[$static_key];
                if (!empty(self::$_static_cache_map[$s_rKey])) {
                    $cache_map[$static_key] = self::$_static_cache_map[$s_rKey];
                    unset($rKeysMap[$static_key]);
                }
            }
        }
        if (!empty($rKeysMap)) {
            $list = $mCache->getItems(array_values($rKeysMap));
            $idx = 0;
            foreach ($rKeysMap as $_data_key => $_r_key) {
                $tmp_item = !empty($list[$idx]) ? $list[$idx] : null;
                $val_str = !empty($tmp_item) ? $tmp_item->get() : '';
                $val = !empty($val_str) ? self::_buildDecodeStr($val_str, $prefix) : [];
                $cache_map[$_data_key] = $val;
                $idx += 1;

                if ($isEnableStaticCache && key_exists('data', $val) && !empty($val['_update_'])) {
                    self::$_static_cache_map[$_data_key] = $val;
                }
            }
        }

        $ret_map = [];
        foreach ($keys as $d_key => $origin_key) {
            $val = !empty($cache_map[$d_key]) ? $cache_map[$d_key] : [];
            $data = null;
            //判断缓存有效期是否在要求之内
            if (key_exists('data', $val) && !empty($val['_update_']) && $now - $val['_update_'] < $timeCache) {
                // $rKeysMap[$d_key] 为空 表示使用的是 类 静态缓存
                self::_cacheDebug('mhit', $now, $method, $origin_key, $timeCache, $val['_update_'], [], empty($rKeysMap[$d_key]), $is_log);
                $data = $val['data'];
            }
            $ret_map[$d_key] = $data;
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
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);

        if (!empty($method) || !empty($key)) {
            $rKey = self::_buildCacheKey($method, $key, $prefix);
            $mCache->deleteItem($rKey);
            if ($isEnableStaticCache && !empty(self::$_static_cache_map[$rKey])) {
                unset(self::$_static_cache_map[$rKey]);
            }
            self::_cacheDebug('delkey', $now, $method, $key, -1, $now, $tags, $isEnableStaticCache, $is_log);
        }
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $mCache->deleteItemsByTag($tag);
                if ($isEnableStaticCache) {
                    $tagKey = "{$prefix}_tags:{$tag}";
                    $_rKeyList = !empty(self::$_static_tags_map[$tagKey]) ? self::$_static_tags_map[$tagKey] : [];
                    foreach ($_rKeyList as $_rKey) {
                        unset(self::$_static_cache_map[$_rKey]);
                    }
                    unset(self::$_static_tags_map[$tagKey]);
                }
            }
            self::_cacheDebug('deltag', $now, $method, $key, -1, $now, $tags, $isEnableStaticCache, $is_log);
        }
    }

    private static function _cacheDataByFastCache($method, $key, callable $func, callable $filter, $timeCache = null, $prefix = null, $tags = [], $is_log = false)
    {
        if (empty($key) || empty($method)) {
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
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);
        $rKey = self::_buildCacheKey($method, $key, $prefix);

        if ($timeCache <= 0) {
            $data = $timeCache == 0 ? $func() : [];
            self::_clearDataByFastCache($method, $key, $prefix, self::_buildTagsByData($tags, $data), $is_log);
            return $data;
        }
        $useStatic = false;
        if ($isEnableStaticCache && !empty(self::$_static_cache_map[$rKey])) {
            $val = self::$_static_cache_map[$rKey];
            $useStatic = true;
        } else {
            $val_str = $mCache->getItem($rKey)->get();
            $val = !empty($val_str) ? self::_buildDecodeStr($val_str, $prefix) : [];
        }

        if (!$useStatic && $isEnableStaticCache && key_exists('data', $val) && !empty($val['_update_'])) {
            self::$_static_cache_map[$rKey] = $val;
            $tags = self::_buildTagsByData($tags, $val['data']);
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $tagKey = "{$prefix}_tags:{$tag}";
                    self::$_static_tags_map[$tagKey] = !empty(self::$_static_tags_map[$tagKey]) ? self::$_static_tags_map[$tagKey] : [];
                    self::$_static_tags_map[$tagKey][$rKey] = 1;
                }
            }
        }

        //判断缓存有效期是否在要求之内  数据符合要求直接返回  不再执行 func
        if (key_exists('data', $val) && !empty($val['_update_']) && $now - $val['_update_'] < $timeCache) {
            self::_cacheDebug('hit', $now, $method, $key, $timeCache, $val['_update_'], $tags, $useStatic, $is_log);
            return $val['data'];
        }

        $data = $func();
        $val = ['data' => $data, '_update_' => time()];
        $use_cache = $filter($val['data']);
        if (is_numeric($use_cache) && $use_cache > 0) {  //当 $filter 返回一个数字时  使用返回结果当作缓存时间
            $timeCache = $use_cache;
        }

        if ($use_cache) {   //需要缓存 且缓存世间大于0 保存数据并加上 tags
            $itemObj = $mCache->getItem($rKey)->set(self::_buildEncodeVal($val, $prefix))->expiresAfter($timeCache);
            if ($isEnableStaticCache) {
                self::$_static_cache_map[$rKey] = $val;
            }
            $tags = self::_buildTagsByData($tags, $data);
            if (!empty($tags)) {
                $itemObj->setTags($tags);
                foreach ($tags as $tag) {
                    $tagKey = "{$prefix}_tags:{$tag}";
                    if ($isEnableStaticCache) {
                        self::$_static_tags_map[$tagKey] = !empty(self::$_static_tags_map[$tagKey]) ? self::$_static_tags_map[$tagKey] : [];
                        self::$_static_tags_map[$tagKey][$rKey] = 1;
                    }
                }
            }
            $mCache->save($itemObj);
            self::_cacheDebug('cache', $now, $method, $key, $timeCache, $val['_update_'], $tags, $isEnableStaticCache, $is_log);
        } else {
            self::_cacheDebug('skip', $now, $method, $key, $timeCache, $val['_update_'], $tags, $isEnableStaticCache, $is_log);
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
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);

        $rKeysMap = [];
        foreach ($keys as $data_key => $origin_key) {
            $rKeysMap[$data_key] = self::_buildCacheKey($method, $origin_key, $prefix);
        }

        if ($timeCache <= 0) {
            $mRedis->del(array_values($rKeysMap));
            if ($isEnableStaticCache) {
                foreach ($rKeysMap as $del_rKey) {
                    unset(self::$_static_cache_map[$del_rKey]);
                }
            }
            self::_cacheDebug('mdel', $now, $method, join(',', $keys), $timeCache, $now, [], $isEnableStaticCache, $is_log);
            return [];
        }

        $cache_map = [];
        if ($isEnableStaticCache) {  // 如果启用了静态缓存  优先使用类中的缓存
            foreach ($keys as $static_key => $_origin_key) {
                $s_rKey = $rKeysMap[$static_key];
                if (!empty(self::$_static_cache_map[$s_rKey])) {
                    $cache_map[$static_key] = self::$_static_cache_map[$s_rKey];
                    unset($rKeysMap[$static_key]);
                }
            }
        }
        if (!empty($rKeysMap)) {
            $list = $mRedis->mget(array_values($rKeysMap));
            $idx = 0;
            foreach ($rKeysMap as $_data_key => $_r_key) {
                $val_str = !empty($list[$idx]) ? $list[$idx] : '';
                $val = !empty($val_str) ? self::_buildDecodeStr($val_str, $prefix) : [];  //判断缓存有效期是否在要求之内
                $cache_map[$_data_key] = $val;
                $idx += 1;

                if ($isEnableStaticCache && key_exists('data', $val) && !empty($val['_update_'])) {
                    self::$_static_cache_map[$_data_key] = $val;
                }
            }
        }

        $ret_map = [];
        foreach ($keys as $d_key => $origin_key) {
            $val = !empty($cache_map[$d_key]) ? $cache_map[$d_key] : [];
            $data = null;
            //判断缓存有效期是否在要求之内
            if (key_exists('data', $val) && !empty($val['_update_']) && $now - $val['_update_'] < $timeCache) {
                // $rKeysMap[$d_key] 为空 表示使用的是 类 静态缓存
                self::_cacheDebug('mhit', $now, $method, $origin_key, $timeCache, $val['_update_'], [], empty($rKeysMap[$d_key]), $is_log);
                $data = $val['data'];
            }
            $ret_map[$d_key] = $data;
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
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);

        if (!empty($method) || !empty($key)) {
            $rKey = self::_buildCacheKey($method, $key, $prefix);
            $mRedis->del($rKey);
            if ($isEnableStaticCache && !empty(self::$_static_cache_map[$rKey])) {
                unset(self::$_static_cache_map[$rKey]);
            }
            self::_cacheDebug('delkey', $now, $method, $key, -1, $now, $tags, $isEnableStaticCache, $is_log);
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
                if ($isEnableStaticCache) {
                    $_rKeyList = !empty(self::$_static_tags_map[$tagKey]) ? self::$_static_tags_map[$tagKey] : [];
                    foreach ($_rKeyList as $_rKey) {
                        unset(self::$_static_cache_map[$_rKey]);
                    }
                    unset(self::$_static_tags_map[$tagKey]);
                }
            }
            self::_cacheDebug('deltag', $now, $method, $key, -1, $now, $tags, $isEnableStaticCache, $is_log);
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
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);

        $rKey = self::_buildCacheKey($method, $key, $prefix);
        if ($timeCache <= 0) {
            $data = $timeCache == 0 ? $func() : [];
            self::_clearDataByRedis($method, $key, $prefix, self::_buildTagsByData($tags, $data), $is_log);
            return $data;
        }
        $useStatic = false;
        if ($isEnableStaticCache && !empty(self::$_static_cache_map[$rKey])) {
            $val = self::$_static_cache_map[$rKey];
            $useStatic = true;
        } else {
            $val_str = $mRedis->get($rKey);
            $val = !empty($val_str) ? self::_buildDecodeStr($val_str, $prefix) : [];  //判断缓存有效期是否在要求之内  数据符合要求直接返回  不再执行 func
        }

        if (!$useStatic && $isEnableStaticCache && key_exists('data', $val) && !empty($val['_update_'])) {
            self::$_static_cache_map[$rKey] = $val;
            $tags = self::_buildTagsByData($tags, $val['data']);
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $tagKey = "{$prefix}_tags:{$tag}";
                    self::$_static_tags_map[$tagKey] = !empty(self::$_static_tags_map[$tagKey]) ? self::$_static_tags_map[$tagKey] : [];
                    self::$_static_tags_map[$tagKey][$rKey] = 1;
                }
            }
        }

        if (key_exists('data', $val) && !empty($val['_update_']) && $now - $val['_update_'] < $timeCache) {
            self::_cacheDebug('hit', $now, $method, $key, $timeCache, $val['_update_'], $tags, $useStatic, $is_log);
            return $val['data'];
        }

        $data = $func();
        $val = ['data' => $data, '_update_' => time()];
        $use_cache = $filter($val['data']);
        if (is_numeric($use_cache) && $use_cache > 0) {  //当 $filter 返回一个数字时  使用返回结果当作缓存时间
            $timeCache = $use_cache;
        }

        if ($use_cache) {   //需要缓存 且缓存时间大于0 保存数据并加上 tags
            $mRedis->setex($rKey, $timeCache, self::_buildEncodeVal($val, $prefix));
            if ($isEnableStaticCache) {
                self::$_static_cache_map[$rKey] = $val;
            }
            $tags = self::_buildTagsByData($tags, $data);
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $tagKey = "{$prefix}_tags:{$tag}";
                    $mRedis->sAdd($tagKey, $rKey);
                    if ($isEnableStaticCache) {
                        self::$_static_tags_map[$tagKey] = !empty(self::$_static_tags_map[$tagKey]) ? self::$_static_tags_map[$tagKey] : [];
                        self::$_static_tags_map[$tagKey][$rKey] = 1;
                    }
                }
            }

            self::_cacheDebug("cache", $now, $method, $key, $timeCache, $val['_update_'], $tags, $isEnableStaticCache, $is_log);
        } else {
            self::_cacheDebug("skip", $now, $method, $key, $timeCache, $val['_update_'], $tags, $isEnableStaticCache, $is_log);
        }

        return $val['data'];
    }


    ####################################################
    ##################### 辅助方法 ####################
    ####################################################

    private static function _isEnableStaticCache($prefix)
    {
        return self::_getCacheConfig($prefix)->isEnableStaticCache();
    }

    private static function _isCacheUseRedis($prefix)
    {
        return self::_getCacheConfig($prefix)->isCacheUseRedis();
    }

    /**
     * @param string | null $key
     * @return CacheConfig
     */
    private static function _getCacheConfig($key = null)
    {
        $tmp = null;
        if (!empty($key) && is_string($key)) {
            $tmp = CacheConfig::loadConfig($key);
        }
        if (empty($tmp)) {
            $tmp = CacheConfig::loadConfig();
        }
        return $tmp;
    }


    private static function _buildEncodeVal($val, $prefix = null)
    {
        return self::_getCacheConfig($prefix)->encodeResolver($val);
    }

    private static function _buildDecodeStr($str, $prefix = null)
    {
        return self::_getCacheConfig($prefix)->decodeResolver($str);
    }

    private static function _buildMethod($method, $prefix = null)
    {
        $method = self::_getCacheConfig($prefix)->methodResolver($method);
        $method = str_replace('::', '.', $method);
        return trim($method);
    }

    private static function _buildPreFix($prefix = null)
    {
        if (is_null($prefix)) {
            $prefix = self::_getCacheConfig()->preFixResolver($prefix);
        }
        if (is_null($prefix)) {
            $prefix = self::$_cache_prefix_key;
        }
        return trim($prefix);
    }

    private static function _buildCacheKey($method, $key, $prefix = null)
    {
        $prefix = self::_buildPreFix($prefix);
        $method = self::_buildMethod($method, $prefix);
        $rKey = !empty($prefix) ? "{$prefix}:{$method}:{$key}" : "{$method}:{$key}";
        return $rKey;
    }

    private static function _buildTagsByData($tags = [], $data = null)
    {
        if (!empty($tags) && is_callable($tags)) {
            return !empty($data) ? call_user_func_array($tags, [$data]) : [];
        }
        if (!empty($tags) && !is_array($tags)) {
            $tags = [$tags];
        }
        return $tags;
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

    private static function _cacheDebug($action, $now, $method, $key, $timeCache, $update, $tags, $useStatic, $is_log)
    {
        if (App::dev()) {
            CacheConfig::doneCacheAction($action, $now, $method, $key, $timeCache, $update, $tags, $useStatic);
        }

        if ($is_log) {
            $useStatic = !empty($useStatic) ? 1 : 0;
            $log_msg = "{$action} now:{$now}, method:{$method}, key:{$key}, timeCache:{$timeCache}, _update_:{$update}, useStatic:{$useStatic}";
            if (!empty($tags) && is_array($tags)) {
                $log_msg .= ", tags:[" . join(',', $tags) . ']';
            }
            LogTrait::debug($log_msg, __METHOD__, __CLASS__, __LINE__);
        }

    }

}