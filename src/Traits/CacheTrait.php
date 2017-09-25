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

trait CacheTrait
{
    protected static $_REDIS_DEFAULT_EXPIRES = 300;
    protected static $_REDIS_PREFIX_CACHE = 'BMCache';

    private static $_mCacheManager = null;

    /**
     * @return null|\phpFastCache\Cache\ExtendedCacheItemPoolInterface
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     */
    public static function getCacheInstance()
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
     * 使用redis缓存函数调用的结果 优先使用缓存中的数据
     * @param string $method 所在方法 方便检索
     * @param string $key redis 缓存tag 表示分类
     * @param callable $func 获取结果的调用 没有任何参数  需要有返回结果
     * @param callable $filter 判断结果是否可以缓存的调用 参数为 $func 的返回结果 返回值为bool
     * @param int $timeCache 允许的数据缓存时间 0表示返回函数结果并清空缓存  负数表示不执行调用只清空缓存  默认为300
     * @param bool $is_log 是否显示日志
     * @param string $prefix 缓存键 的 前缀
     * @param array $tags 标记数组
     * @return array
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     */
    public static function _cacheDataManager($method, $key, callable $func, callable $filter, $timeCache = null, $is_log = false, $prefix = null, array $tags = [])
    {
        $mCache = self::getCacheInstance();
        if (empty($key) || empty($method) || empty($mCache)) {
            return $func();
        }

        $prefix = is_null($prefix) ? static::$_REDIS_PREFIX_CACHE : $prefix;
        $timeCache = is_null($timeCache) ? static::$_REDIS_DEFAULT_EXPIRES : $timeCache;
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
            $timeCache = intval($use_cache);
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

    protected static function _redisDebug($action, $now, $method, $key, $timeCache, $update, $tags)
    {
        $log_msg = "{$action} now:{$now}, method:{$method}, key:{$key}, timeCache:{$timeCache}, _update_:{$update}";
        if (!empty($tags)) {
            $log_msg .= ", tags:[" . join(',', $tags) . ']';
        }
        LogTrait::debug($log_msg, __METHOD__, __CLASS__, __LINE__);
    }

}