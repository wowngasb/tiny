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
use Tiny\Plugin\MyYac;
use Tiny\Util;

trait CacheTrait
{
    private static $_static_cache_map = [];
    private static $_static_tags_map = [];

    private static $_cache_default_expires = 300;
    private static $_cache_prefix_key = 'Cache';
    private static $_cache_max_key_len = 128;

    private static $_mCacheManager = null;

    private static $_redis_instance_monk = null;


    ############################################################
    ########################## 对外方法 #######################
    ############################################################

    public static function _hashKey($args_input = [], $tag = "no_args")
    {
        $args_list = [];
        if (!empty($args_input)) {
            foreach ($args_input as $key => $val) {
                $key = trim($key);
                $val = self::_fix_cache_key($val);
                if (!empty($key)) {
                    $args_list[] = "{$key}=" . urlencode($val);
                }
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
     * @return MyYac
     */
    public static function _getYacInstance($prefix = '')
    {
        try {
            $yac = self::_get_yac($prefix);
            if (!empty($yac)) {
                return $yac;
            }
        } catch (\Exception $e) {
            //忽略异常 尝试返回一个空的 mock 对象
            error_log("create redis with error:" . $e->getMessage());
        }

        if (empty(self::$_yac_instance_monk)) {
            /** @var MyYac $yac */
            $yac = new EmptyMock();
            self::$_yac_instance_monk = $yac;
        }
        return self::$_yac_instance_monk;
    }

    /**
     * @param string $prefix
     * @return \Redis
     */
    public static function _getRedisInstance($prefix = '', $use_predis = false)
    {
        try {
            $redis = self::_get_redis($prefix, $use_predis);
            if (!empty($redis)) {
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
     * @param callable $filter 判断结果是否可以缓存的调用 参数为 $func 的返回结果 返回值为bool  int
     * @param int | null $timeCache 允许的数据缓存时间 0表示返回函数结果并清空缓存  负数表示不执行调用只清空缓存  默认为300
     * @param string $prefix 缓存键 的 前缀
     * @param array | Closure $tags 标记数组
     * @param bool $is_log 是否显示日志
     * @return mixed
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
     * @param int | null $timeCache $timeCache 0 执行函数 返回结果   -1 清除缓存 返回空   小于等于 -2  执行函数 返回结果 并设置缓存 缓存时间为 -$timeCache
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

        $prefix_ = self::_buildPreFix($prefix);
        $mCache = self::_getCacheInstance();
        if (empty($mCache)) {
            error_log(__METHOD__ . ' can not get mCache by _getCacheInstance ' . __METHOD__);
            return [];
        }

        $now = time();
        $timeCache = is_null($timeCache) ? self::$_cache_prefix_key : $timeCache;
        $timeCache = intval($timeCache);
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);
        $isEnableYacCache = self::_isEnableYacCache($prefix);

        $rKeysMap = [];
        foreach ($keys as $data_key => $origin_key) {
            $rKeysMap[$data_key] = self::_buildCacheKey($method, $origin_key, $prefix);
        }

        if ($timeCache <= 0) {
            $del_rKeys = array_values($rKeysMap);
            $mCache->deleteItems($del_rKeys);
            if ($isEnableYacCache) {
                $mYac = self::_getYacInstance($prefix_);
                if (empty($mYac) || $mYac instanceof EmptyMock) {
                    error_log(__METHOD__ . ' can not get mYac by _getYacInstance' . __METHOD__);
                } else {
                    $mYac->del($del_rKeys);
                }
            }
            if ($isEnableStaticCache) {
                foreach ($rKeysMap as $del_rKey) {
                    unset(self::$_static_cache_map[$del_rKey]);
                }
            }
            self::_cacheDebug('mdel', $now, $method, join(',', $keys), $timeCache, $now, [], $isEnableStaticCache, $isEnableYacCache, $is_log);
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

        $tmpYacMap = [];
        if ($isEnableYacCache && !empty($rKeysMap)) {
            $mYac = self::_getYacInstance($prefix_);
            if (empty($mYac) || $mYac instanceof EmptyMock) {
                error_log(__METHOD__ . ' can not get mYac by _getYacInstance' . __METHOD__);
            } else {
                $list = $mYac->mget(array_values($rKeysMap));
                $idx = 0;
                $_del_keys = [];
                foreach ($rKeysMap as $_data_key => $_r_key) {
                    $val_str = !empty($list[$idx]) ? $list[$idx] : '';
                    if (!empty($val_str)) {
                        $tmpYacMap[$_data_key] = $val_str;
                        $_del_keys[] = $_data_key;
                    }
                    $idx += 1;
                }
                foreach ($_del_keys as $del_key) {
                    unset($rKeysMap[$del_key]);
                }
            }
        }

        $tmpRedisMap = [];
        if (!empty($rKeysMap)) {
            $list = $mCache->getItems(array_values($rKeysMap));
            $idx = 0;
            foreach ($rKeysMap as $_data_key => $_r_key) {
                $tmp_item = !empty($list[$idx]) ? $list[$idx] : null;
                $val_str = !empty($tmp_item) ? $tmp_item->get() : '';
                if (!empty($val_str)) {
                    $tmpRedisMap[$_data_key] = $val_str;
                }
                $idx += 1;
            }
        }

        $len_map = [];
        // 尝试解码 tmpMap 中的数据
        $tmpMap = $tmpYacMap + $tmpRedisMap;  // 保持原key
        foreach ($tmpMap as $_data_key => $val_str) {
            $val = !empty($val_str) ? self::_buildDecodeStr($val_str, $prefix) : [];
            $cache_map[$_data_key] = $val;
            $len_map[$_data_key] = !empty($val_str) ? strlen($val_str) : 0;

            if (!empty($val) && key_exists('data', $val) && !empty($val['_update_'])) {
                if ($isEnableStaticCache) {
                    self::$_static_cache_map[$_data_key] = $val;
                }
            }
        }

        $ret_map = [];
        foreach ($keys as $d_key => $origin_key) {
            $val = !empty($cache_map[$d_key]) ? $cache_map[$d_key] : [];
            $bytes = !empty($len_map[$d_key]) ? $len_map[$d_key] : 0;
            $data = null;
            //判断缓存有效期是否在要求之内
            if (!empty($val) && key_exists('data', $val) && !empty($val['_update_']) && $now - $val['_update_'] < $timeCache) {
                // $rKeysMap[$d_key] 为空 表示使用的是 类 静态缓存
                self::_cacheDebug('mhit', $now, $method, $origin_key, $timeCache, $val['_update_'], [], !empty($tmpRedisMap[$d_key]), !empty($tmpYacMap[$d_key]), $is_log, $bytes);
                $data = $val['data'];
            }
            $ret_map[$d_key] = $data;
        }
        return $ret_map;
    }

    private static function _clearDataByFastCache($method = '', $key = '', $prefix = null, $tags = [], $is_log = false)
    {
        $mCache = self::_getCacheInstance();
        if (empty($mCache)) {
            error_log(__METHOD__ . ' can not get mCache by _getCacheInstance ' . __METHOD__);
            return;
        }
        $now = time();
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);
        $isEnableYacCache = self::_isEnableYacCache($prefix);

        if (!empty($method) || !empty($key)) {
            $rKey = self::_buildCacheKey($method, $key, $prefix);
            $mCache->deleteItem($rKey);
            if ($isEnableYacCache) {
                $mYac = self::_getYacInstance($prefix_);
                if (empty($mYac) || $mYac instanceof EmptyMock) {
                    error_log(__METHOD__ . ' can not get mYac by _getYacInstance' . __METHOD__);
                } else {
                    $mYac->del($rKey);
                }
            }
            if ($isEnableStaticCache && !empty(self::$_static_cache_map[$rKey])) {
                unset(self::$_static_cache_map[$rKey]);
            }
            self::_cacheDebug('delkey', $now, $method, $key, -1, $now, $tags, $isEnableStaticCache, $isEnableYacCache, $is_log);
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
            self::_cacheDebug('deltag', $now, $method, $key, -1, $now, $tags, $isEnableStaticCache, 0, $is_log);
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
        $isEnableYacCache = self::_isEnableYacCache($prefix);

        $rKey = self::_buildCacheKey($method, $key, $prefix);

        if ($timeCache <= 0) {
            // $timeCache 0 执行函数 返回结果   -1 清除缓存 返回空   小于等于 -2  执行函数 返回结果 并设置缓存 缓存时间为 -$timeCache
            $data = ($timeCache == 0 || $timeCache == -2) ? $func() : [];
            if ($timeCache == -2) {
                self::_setDataByFastCache($method, $key, $prefix, $data, $filter, $timeCache, $tags, $is_log);
            } else {
                self::_clearDataByFastCache($method, $key, $prefix, self::_buildTagsByData($tags, $data), $is_log);
            }

            return $data;
        }
        $useStatic = false;
        $useYac = false;

        $bytes = 0;
        if ($isEnableStaticCache && !empty(self::$_static_cache_map[$rKey])) {
            $val = self::$_static_cache_map[$rKey];
            $useStatic = true;
        } else {
            $val_str = '';
            if ($isEnableYacCache) {
                $mYac = self::_getYacInstance($prefix_);
                if (empty($mYac) || $mYac instanceof EmptyMock) {
                    error_log(__METHOD__ . ' can not get mYac by _getYacInstance' . __METHOD__);
                } else {
                    $val_str = $mYac->get($rKey);
                    $useYac = true;
                }
            }
            $val_str = !empty($val_str) ? $val_str : $mCache->getItem($rKey)->get();
            $bytes = !empty($val_str) ? strlen($val_str) : 0;
            $val = !empty($val_str) ? self::_buildDecodeStr($val_str, $prefix) : [];
        }

        if (!$useStatic && $isEnableStaticCache && !empty($val) && key_exists('data', $val) && !empty($val['_update_'])) {
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
        if (!empty($val) && key_exists('data', $val) && !empty($val['_update_']) && $now - $val['_update_'] < $timeCache) {
            self::_cacheDebug('hit', $now, $method, $key, $timeCache, $val['_update_'], $tags, $useStatic, $useYac, $is_log, $bytes);
            return $val['data'];
        }

        $data = $func();
        self::_setDataByFastCache($method, $key, $prefix, $data, $filter, $timeCache, $tags, $is_log);

        return $data;
    }

    private static function _setDataByFastCache($method, $key, $prefix, $data, $filter, $timeCache, $tags, $is_log)
    {
        $mCache = self::_getCacheInstance();
        if (empty($mCache)) {
            error_log(__METHOD__ . ' can not get mCache by _getCacheInstance!');
            return;
        }

        $now = time();
        $timeCache = is_null($timeCache) ? self::$_cache_default_expires : $timeCache;
        $timeCache = intval($timeCache);
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);
        $isEnableYacCache = self::_isEnableYacCache($prefix);

        $rKey = self::_buildCacheKey($method, $key, $prefix);

        $val = ['data' => $data, '_update_' => time()];
        $use_cache = $filter($val['data']);
        if (is_numeric($use_cache) && $use_cache > 0) {  //当 $filter 返回一个数字时  使用返回结果当作缓存时间
            $timeCache = $use_cache;
        }

        if ($use_cache) {   //需要缓存 且缓存世间大于0 保存数据并加上 tags
            $val_str = self::_buildEncodeVal($val, $prefix);
            $bytes = !empty($val_str) ? strlen($val_str) : 0;
            $itemObj = $mCache->getItem($rKey)->set($val_str)->expiresAfter($timeCache);
            if ($isEnableYacCache) {
                $mYac = self::_getYacInstance($prefix_);
                if (empty($mYac) || $mYac instanceof EmptyMock) {
                    error_log(__METHOD__ . ' can not get mYac by _getYacInstance' . __METHOD__);
                } else {
                    $mYac->setx($rKey, $timeCache, $val_str);
                }
            }
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
            self::_cacheDebug('cache', $now, $method, $key, $timeCache, $val['_update_'], $tags, $isEnableStaticCache, $isEnableYacCache, $is_log, $bytes);
        } else {
            self::_cacheDebug('skip', $now, $method, $key, $timeCache, $val['_update_'], $tags, $isEnableStaticCache, $isEnableYacCache, $is_log);
        }
    }

    public static function _mgetDataByRedis($method, array $keys, $timeCache = null, $prefix = null, $is_log = false)
    {
        if (empty($keys) || empty($method)) {
            error_log("call _mgetDataByRedis with empty method or keys " . __METHOD__);
            return [];
        }
        $prefix_ = self::_buildPreFix($prefix);
        $mRedis = self::_getRedisInstance($prefix_);
        if (empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not get mRedis by _getRedisInstance ' . __METHOD__);
            return self::_mgetDataByFastCache($method, $keys, $timeCache, $prefix, $is_log);
        }

        $now = time();
        $timeCache = is_null($timeCache) ? self::$_cache_prefix_key : $timeCache;
        $timeCache = intval($timeCache);
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);
        $isEnableYacCache = self::_isEnableYacCache($prefix);

        $rKeysMap = [];
        foreach ($keys as $data_key => $origin_key) {
            $rKeysMap[$data_key] = self::_buildCacheKey($method, $origin_key, $prefix);
        }

        if ($timeCache <= 0) {
            $del_rKeys = array_values($rKeysMap);
            $mRedis->del($del_rKeys);
            if ($isEnableYacCache) {
                $mYac = self::_getYacInstance($prefix_);
                if (empty($mYac) || $mYac instanceof EmptyMock) {
                    error_log(__METHOD__ . ' can not get mYac by _getYacInstance' . __METHOD__);
                } else {
                    $mYac->del($del_rKeys);
                }
            }
            if ($isEnableStaticCache) {
                foreach ($rKeysMap as $del_rKey) {
                    unset(self::$_static_cache_map[$del_rKey]);
                }
            }
            self::_cacheDebug('mdel', $now, $method, join(',', $keys), $timeCache, $now, [], $isEnableStaticCache, $isEnableYacCache, $is_log);
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

        $tmpYacMap = [];
        if ($isEnableYacCache && !empty($rKeysMap)) {
            $mYac = self::_getYacInstance($prefix_);
            if (empty($mYac) || $mYac instanceof EmptyMock) {
                error_log(__METHOD__ . ' can not get mYac by _getYacInstance' . __METHOD__);
            } else {
                $rKeys = array_values($rKeysMap);
                $list = $mYac->mget($rKeys);
                $idx = 0;
                $_del_keys = [];
                foreach ($rKeysMap as $_data_key => $_r_key) {
                    $val_str = !empty($list[$idx]) ? $list[$idx] : '';
                    if (!empty($val_str)) {
                        $tmpYacMap[$_data_key] = $val_str;
                        $_del_keys[] = $_data_key;
                    }
                    $idx += 1;
                }
                foreach ($_del_keys as $del_key) {
                    unset($rKeysMap[$del_key]);
                }
            }
        }

        $tmpRedisMap = [];
        if (!empty($rKeysMap)) {
            $rKeys = array_values($rKeysMap);
            $list = $mRedis->mget($rKeys);
            $idx = 0;
            foreach ($rKeysMap as $_data_key => $_r_key) {
                $val_str = !empty($list[$idx]) ? $list[$idx] : '';
                if (!empty($val_str)) {
                    $tmpRedisMap[$_data_key] = $val_str;
                }
                $idx += 1;
            }
        }

        $len_map = [];
        // 尝试解码 tmpMap 中的数据
        $tmpMap = $tmpYacMap + $tmpRedisMap;  // 保持原key
        foreach ($tmpMap as $_data_key => $val_str) {
            $val = !empty($val_str) ? self::_buildDecodeStr($val_str, $prefix) : [];
            $cache_map[$_data_key] = $val;
            $len_map[$_data_key] = !empty($val_str) ? strlen($val_str) : 0;

            if (!empty($val) && key_exists('data', $val) && !empty($val['_update_'])) {
                if ($isEnableStaticCache) {
                    self::$_static_cache_map[$_data_key] = $val;
                }
            }
        }

        $ret_map = [];
        foreach ($keys as $d_key => $origin_key) {
            $val = !empty($cache_map[$d_key]) ? $cache_map[$d_key] : [];
            $bytes = !empty($len_map[$d_key]) ? $len_map[$d_key] : 0;
            $data = null;
            //判断缓存有效期是否在要求之内
            if (!empty($val) && key_exists('data', $val) && !empty($val['_update_']) && $now - $val['_update_'] < $timeCache) {
                // $rKeysMap[$d_key] 为空 表示使用的是 类 静态缓存
                self::_cacheDebug('mhit', $now, $method, $origin_key, $timeCache, $val['_update_'], [], !empty($tmpRedisMap[$d_key]), !empty($tmpYacMap[$d_key]), $is_log, $bytes);
                $data = $val['data'];
            }
            $ret_map[$d_key] = $data;
        }
        return $ret_map;
    }

    private static function _clearDataByRedis($method = '', $key = '', $prefix = null, $tags = [], $is_log = false, $enable_yac = false)
    {
        $prefix_ = self::_buildPreFix($prefix);
        $mRedis = self::_getRedisInstance($prefix_);
        if (empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not get mRedis by _getRedisInstance' . __METHOD__);
            self::_clearDataByFastCache($method, $key, $prefix, $tags, $is_log);
            return;
        }
        $now = time();
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);
        $isEnableYacCache = self::_isEnableYacCache($prefix);

        if (!empty($method) || !empty($key)) {
            $rKey = self::_buildCacheKey($method, $key, $prefix);
            $mRedis->del($rKey);
            if ($isEnableYacCache) {
                $mYac = self::_getYacInstance($prefix_);
                if (empty($mYac) || $mYac instanceof EmptyMock) {
                    error_log(__METHOD__ . ' can not get mYac by _getYacInstance' . __METHOD__);
                } else {
                    $mYac->del($rKey);
                }
            }
            if ($isEnableStaticCache && !empty(self::$_static_cache_map[$rKey])) {
                unset(self::$_static_cache_map[$rKey]);
            }
            self::_cacheDebug('delkey', $now, $method, $key, -1, $now, $tags, $isEnableStaticCache, $isEnableYacCache, $is_log);
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
            self::_cacheDebug('deltag', $now, $method, $key, -1, $now, $tags, $isEnableStaticCache, 0, $is_log);
        }
    }

    private static function _cacheDataByRedis($method, $key, callable $func, callable $filter, $timeCache = null, $prefix = null, $tags = [], $is_log = false)
    {
        if (empty($key) || empty($method)) {
            error_log("call _cacheDataByRedis with empty method or key " . __METHOD__);
            return [];
        }
        $prefix_ = self::_buildPreFix($prefix);
        $mRedis = self::_getRedisInstance($prefix_);
        if (empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not get mRedis by _cacheDataByRedis' . __METHOD__);
            return self::_cacheDataByFastCache($method, $key, $func, $filter, $timeCache, $prefix, $tags, $is_log);
        }

        $now = time();
        $timeCache = is_null($timeCache) ? self::$_cache_prefix_key : $timeCache;
        $timeCache = intval($timeCache);
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);
        $isEnableYacCache = self::_isEnableYacCache($prefix);

        $rKey = self::_buildCacheKey($method, $key, $prefix);
        if ($timeCache <= 0) {
            // $timeCache 0 执行函数 清除缓存 返回结果   -1 清除缓存 返回空   小于等于 -2  执行函数 返回结果 并设置缓存 缓存时间为 -$timeCache
            $data = ($timeCache == 0 || $timeCache <= -2) ? $func() : [];
            if ($timeCache <= -2) {
                self::_setDataByRedis($method, $key, $prefix, $data, $filter, -$timeCache, $tags, $is_log);
            } else {
                self::_clearDataByRedis($method, $key, $prefix, self::_buildTagsByData($tags, $data), $is_log);
            }
            return $data;
        }
        $useStatic = false;
        $useYac = false;

        $bytes = 0;
        if ($isEnableStaticCache && !empty(self::$_static_cache_map[$rKey])) {
            $val = self::$_static_cache_map[$rKey];
            $useStatic = true;
        } else {
            $val_str = '';
            if ($isEnableYacCache) {
                $mYac = self::_getYacInstance($prefix_);
                if (empty($mYac) || $mYac instanceof EmptyMock) {
                    error_log(__METHOD__ . ' can not get mYac by _getYacInstance' . __METHOD__);
                } else {
                    $val_str = $mYac->get($rKey);
                    $useYac = true;
                }
            }
            $val_str = !empty($val_str) ? $val_str : $mRedis->get($rKey);
            $bytes = !empty($val_str) ? strlen($val_str) : 0;
            $val = !empty($val_str) ? self::_buildDecodeStr($val_str, $prefix) : [];  //判断缓存有效期是否在要求之内  数据符合要求直接返回  不再执行 func
        }

        if (!$useStatic && $isEnableStaticCache && !empty($val) && key_exists('data', $val) && !empty($val['_update_'])) {
            self::$_static_cache_map[$rKey] = $val;
            $tags = self::_buildTagsByData($tags, $val['data']);
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $tagKey = "{$prefix_}_tags:{$tag}";
                    self::$_static_tags_map[$tagKey] = !empty(self::$_static_tags_map[$tagKey]) ? self::$_static_tags_map[$tagKey] : [];
                    self::$_static_tags_map[$tagKey][$rKey] = 1;
                }
            }
        }

        if (!empty($val) && key_exists('data', $val) && !empty($val['_update_']) && $now - $val['_update_'] < $timeCache) {
            self::_cacheDebug('hit', $now, $method, $key, $timeCache, $val['_update_'], $tags, $useStatic, $useYac, $is_log, $bytes);
            return $val['data'];
        }

        $data = $func();
        self::_setDataByRedis($method, $key, $prefix, $data, $filter, $timeCache, $tags, $is_log);

        return $data;
    }


    private static function _setDataByRedis($method, $key, $prefix, $data, $filter, $timeCache, $tags, $is_log)
    {
        $prefix_ = self::_buildPreFix($prefix);
        $mRedis = self::_getRedisInstance($prefix_);
        if (empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not get mRedis by _getRedisInstance' . __METHOD__);
            return;
        }
        $now = time();
        $timeCache = is_null($timeCache) ? self::$_cache_prefix_key : $timeCache;
        $timeCache = intval($timeCache);
        $isEnableStaticCache = self::_isEnableStaticCache($prefix);
        $isEnableYacCache = self::_isEnableYacCache($prefix);

        $rKey = self::_buildCacheKey($method, $key, $prefix);

        $val = ['data' => $data, '_update_' => time()];
        $use_cache = $filter($val['data']);
        if (is_numeric($use_cache) && $use_cache > 0) {  //当 $filter 返回一个数字时  使用返回结果当作缓存时间
            $timeCache = $use_cache;
        }

        if ($use_cache) {   //需要缓存 且缓存时间大于0 保存数据并加上 tags
            $val_str = self::_buildEncodeVal($val, $prefix);
            $bytes = !empty($val_str) ? strlen($val_str) : 0;
            $mRedis->setex($rKey, $timeCache, $val_str);
            if ($isEnableYacCache) {
                $mYac = self::_getYacInstance($prefix_);
                if (empty($mYac) || $mYac instanceof EmptyMock) {
                    error_log(__METHOD__ . ' can not get mYac by _getYacInstance' . __METHOD__);
                } else {
                    $mYac->setex($rKey, $timeCache, $val_str);
                }
            }
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

            self::_cacheDebug("cache", $now, $method, $key, $timeCache, $val['_update_'], $tags, $isEnableStaticCache, $isEnableYacCache, $is_log, $bytes);
        } else {
            self::_cacheDebug("skip", $now, $method, $key, $timeCache, $val['_update_'], $tags, $isEnableStaticCache, $isEnableYacCache, $is_log);
        }
    }

    ####################################################
    ##################### 辅助方法 ####################
    ####################################################

    private static function _isEnableStaticCache($prefix)
    {
        return self::_getCacheConfig()->isEnableStaticCache($prefix);
    }

    private static function _isEnableYacCache($prefix)
    {
        return self::_getCacheConfig()->isEnableYacCache($prefix);
    }

    private static function _isCacheUseRedis($prefix)
    {
        return self::_getCacheConfig()->isCacheUseRedis($prefix);
    }

    /**
     * @return CacheConfig
     */
    private static function _getCacheConfig()
    {
        return CacheConfig::loadConfig();
    }


    private static function _buildEncodeVal($val, $prefix = null)
    {
        return self::_getCacheConfig()->encodeResolver($prefix, $val);
    }

    private static function _buildDecodeStr($str, $prefix = null)
    {
        return self::_getCacheConfig()->decodeResolver($prefix, $str);
    }

    private static function _buildMethod($method, $prefix = null)
    {
        $method = self::_getCacheConfig()->methodResolver($prefix, $method);
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

    private static function _get_redis($prefix = '', $use_predis = false)
    {
        if ($use_predis) {
            if (!class_exists('Predis\Client')) {
                return null;
            }
        } else {
            if (!class_exists('Redis')) {
                return null;
            }
        }

        $redisConfig = self::_getCacheConfig()->redisConfig($prefix);

        $host = Util::v($redisConfig, 'host', '127.0.0.1');
        $port = intval(Util::v($redisConfig, 'port', 6379));
        $password = Util::v($redisConfig, 'password', '');
        $database = intval(Util::v($redisConfig, 'database', 0));
        $timeout = intval(Util::v($redisConfig, 'timeout', 5));

        $config_key = str_replace('.', '#', "_Redis_{$host}_{$port}_{$password}_{$database}_{$timeout}" . ($use_predis ? '@predis' : '@redis'));

        $redis = Application::config($config_key, null);
        if (empty($redis)) {
            if ($use_predis) {
                $redis = new \Predis\Client([
                    'scheme' => 'tcp',
                    'host' => $host,
                    'port' => 6379,
                ]);
                if (!empty($redis) && !empty($password)) {
                    $redis = $redis->auth($password) ? $redis : null;
                }
                if (!empty($redis) && $database > 0) {
                    $redis = $redis->select($database) ? $redis : null;
                }
            } else {
                $redis = new \Redis();
                if (!$redis->connect($host, $port, $timeout)) {
                    $redis = null;
                }
                if (!empty($redis) && !empty($password)) {
                    $redis = $redis->auth($password) ? $redis : null;
                }
                if (!empty($redis) && $database > 0) {
                    $redis = $redis->select($database) ? $redis : null;
                }
            }

            if (!empty($redis)) {
                Application::set_config($config_key, $redis);
            }
        }

        return $redis;
    }

    private static function _get_yac($prefix = '')
    {
        if (!class_exists('Yac')) {
            return null;
        }

        $config_key = str_replace('.', '#', "_Yac_{$prefix}");

        $yac = Application::config($config_key, null);
        if (empty($yac)) {
            $_yac = new \Yac($prefix);
            $yac = new MyYac($_yac);
        }

        return $yac;
    }

    private static function _cacheDebug($action, $now, $method, $key, $timeCache, $update, $tags, $useStatic, $useYac, $is_log, $bytes = 0)
    {
        if (App::dev()) {
            CacheConfig::doneCacheAction($action, $now, $method, $key, $timeCache, $update, $tags, $useStatic, $useYac, $bytes);
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