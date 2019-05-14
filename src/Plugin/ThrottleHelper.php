<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/11/14 0014
 * Time: 21:29
 */

namespace Tiny\Plugin;


use app\Util;
use Tiny\Application;
use Tiny\Traits\CacheTrait;
use Tiny\Traits\LogTrait;

class ThrottleHelper
{

    const DEFAULT_PRE_KEY = "throttle";
    const DEFAULT_REFUSE_SEC = 3600;
    const DEFAULT_ALIVE_SEC = 1;
    const DEFAULT_RECORD_INDEX = 0;
    const DEFAULT_SCORE_BASE = 60;

    public static function loadIpList($pre_key, array $acc_seq, $per_day = 0, $ip = '', $page = 1, $num = 50, $sort_asc = 0)
    {
        $per_day = !empty($per_day) ? intval($per_day) : date('Ymd');
        $page = $page > 1 ? intval($page) : 1;
        $num = $num > 10 ? intval($num) : 50;
        $offset = ($page - 1) * $num;

        $mRedis = CacheTrait::_getRedisInstance();
        if (empty($pre_key) || empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not loadIpList  mRedis by _getRedisInstance ');
            return [];
        }
        $now = time();
        $base_score = intval($now / self::DEFAULT_SCORE_BASE) * self::DEFAULT_SCORE_BASE;

        $defaultIpInfo = [];
        foreach ($acc_seq as $seq => $limit) {
            $defaultIpInfo[] = [
                'seq' => $seq,
                'num' => 0,
                'limit' => $limit,
            ];
        }

        $zsetKey = "{$pre_key}:lip:{$per_day}";
        $ret = [];
        if (!empty($ip)) {
            $refuse = self::loadIpRefuse($pre_key, $ip);
            $score = $mRedis->zScore($zsetKey, $ip);
            $ipInfo = self::loadIpInfo($pre_key, $acc_seq, $ip);
            if (!empty($score)) {
                ($refuse <= $now && $score <= $base_score - self::DEFAULT_SCORE_BASE) && $mRedis->zDelete($zsetKey, $ip);
                $score = $score > $base_score ? (intval($score) - $base_score) : 0;
            }
            $ret[] = [
                'ip' => $ip,
                'score' => !empty($score) ? intval($score) : 0,
                'info' => !empty($ipInfo) ? $ipInfo : $defaultIpInfo,
                'refuse' => $refuse,
            ];
        } else {
            $list = $sort_asc ? $mRedis->zRangeByScore($zsetKey, '-inf', '+inf', ['withscores' => true, 'limit' => [$offset, $num]]) : $mRedis->zRevRangeByScore($zsetKey, '+inf', '-inf', ['withscores' => true, 'limit' => [$offset, $num]]);
            $ipInfoMap = self::loadIpInfoMap($pre_key, $acc_seq, $list);
            foreach ($list as $_ip => $_score) {
                $refuse = self::loadIpRefuse($pre_key, $_ip);
                if (!empty($_score)) {
                    ($refuse <= $now && $_score <= $base_score - self::DEFAULT_SCORE_BASE) && $mRedis->zDelete($zsetKey, $_ip);
                    $_score = $_score > $base_score ? (intval($_score) - $base_score) : 0;
                }
                $ret[] = [
                    'ip' => $_ip,
                    'score' => !empty($_score) ? intval($_score) : 0,
                    'info' => !empty($ipInfoMap[$_ip]) ? $ipInfoMap[$_ip] : $defaultIpInfo,
                    'refuse' => $refuse,
                ];
            }
        }

        return $ret;
    }

    public static function countIpList($pre_key, $per_day = 0)
    {
        $per_day = !empty($per_day) ? intval($per_day) : date('Ymd');

        $mRedis = CacheTrait::_getRedisInstance();
        if (empty($pre_key) || empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not countIpList  mRedis by _getRedisInstance ');
            return 0;
        }
        $zsetKey = "{$pre_key}:lip:{$per_day}";
        $ret = $mRedis->zCount($zsetKey, '-inf', '+inf');
        return !empty($ret) ? intval($ret) : $ret;
    }

    public static function loadIpRefuse($pre_key, $ip)
    {
        $refuseKey = "{$pre_key}:rip:{$ip}";

        $yac = class_exists('Yac') ? new \Yac("{$pre_key}_") : null;
        if (!empty($yac)) {
            /** @var mixed $yac */
            $t = $yac->get($refuseKey);
            if (!empty($t)) {
                return intval($t);
            }
        }
        $mRedis = CacheTrait::_getRedisInstance();
        if (empty($pre_key) || empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not loadIpRefuse  mRedis by _getRedisInstance ');
            return 0;
        }
        $t = $mRedis->get($refuseKey);
        if (!empty($t)) {
            return intval($t);
        }
        return 0;
    }

    public static function loadIpInfoMap($pre_key, array $acc_seq, array $ip_list)
    {
        $mRedis = CacheTrait::_getRedisInstance();
        if (empty($pre_key) || empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not loadIpInfo  mRedis by _getRedisInstance ');
            return [];
        }

        $now = time();
        $keyMap = [];
        $valMap = [];
        foreach ($acc_seq as $seq => $limit) {
            $timer_count = intval($now / $seq);
            foreach ($ip_list as $ip => $score) {
                $sKey = "{$pre_key}:ip:{$ip}:{$timer_count}";
                $keyMap["{$ip}#{$seq}"] = $sKey;
            }
            if (count($keyMap) >= 50) {
                $retMap = $mRedis->mget(array_values($keyMap));
                $idx = 0;
                foreach ($keyMap as $k => $rKey) {
                    $valMap[$k] = !empty($retMap[$idx]) ? intval($retMap[$idx]) : 0;
                    $idx++;
                }
                $keyMap = [];
            }
        }

        if (!empty($keyMap)) {
            $retMap = $mRedis->mget(array_values($keyMap));
            $idx = 0;
            foreach ($keyMap as $k => $rKey) {
                $valMap[$k] = !empty($retMap[$idx]) ? intval($retMap[$idx]) : 0;
                $idx++;
            }
        }


        $ret = [];
        foreach ($ip_list as $ip => $score) {
            $ret[$ip] = !empty($ret[$ip]) ? $ret[$ip] : [];
            foreach ($acc_seq as $seq => $limit) {
                $k = "{$ip}#{$seq}";
                $ret[$ip][] = [
                    'seq' => $seq,
                    'num' => !empty($valMap[$k]) ? intval($valMap[$k]) : 0,
                    'limit' => $limit,
                ];
            }
        }

        return $ret;
    }

    public static function loadIpInfo($pre_key, array $acc_seq, $ip)
    {
        $mRedis = CacheTrait::_getRedisInstance();
        if (empty($pre_key) || empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not loadIpInfo  mRedis by _getRedisInstance ');
            return [];
        }

        $now = time();
        $keyArr = [];
        foreach ($acc_seq as $seq => $limit) {
            $timer_count = intval($now / $seq);
            $sKey = "{$pre_key}:ip:{$ip}:{$timer_count}";
            $keyArr[] = $sKey;
        }

        $ret = [];
        $tmpArr = $mRedis->mget($keyArr);
        $idx = 0;
        foreach ($acc_seq as $seq => $limit) {
            $ret[] = [
                'seq' => $seq,
                'num' => !empty($tmpArr[$idx]) ? intval($tmpArr[$idx]) : 0,
                'limit' => $limit,
            ];
            $idx++;
        }
        return $ret;
    }

    /**
     * 检查 ip 是否可以通过 访问频率 检查
     * @param Application $app
     * @param string $ip
     * @param string $request_uri
     * @return int  大于 0  允许通过   小于等于0  屏蔽
     */
    public static function checkPassIpThrottle(Application $app, $ip, $request_uri)
    {
        $origin_throttle = $app::config('services.throttle', []);
        $pre_key = !empty($origin_throttle['pre_key']) ? $origin_throttle['pre_key'] : self::DEFAULT_PRE_KEY;
        /** @var mixed $yac */
        $yac = class_exists('Yac') ? new \Yac("{$pre_key}_") : null;

        $settingKey = "{$pre_key}:setting";
        $last_throttle = !empty($yac) ? $yac->get($settingKey) : '';  // 优先使用 yac  读取配置  没有读取到  尝试 使用 redis 读取
        $throttle = !empty($last_throttle) ? json_decode($last_throttle, true) : [];
        if (empty($throttle)) {
            $mRedis = CacheTrait::_getRedisInstance();
            if (empty($pre_key) || empty($mRedis) || $mRedis instanceof EmptyMock) {
                error_log(__METHOD__ . ' can not loadIpSetting  mRedis by _getRedisInstance ');
                $throttle = $origin_throttle;
            } else {
                $settingKey = "{$pre_key}:setting";
                $json_str = $mRedis->get($settingKey);
                $json = !empty($json_str) ? json_decode($json_str, true) : [];
                $throttle = self::mergeIpSetting($origin_throttle, $json);
            }
            !empty($yac) && $yac->set($settingKey, json_encode($throttle), 30);
        }

        if (empty($throttle['enable'])) {
            return 10;   // 未启用
        }

        $skip_pre = !empty($throttle['skip_pre']) ? $throttle['skip_pre'] : [];
        foreach ($skip_pre as $pre) {
            if (Util::stri_startwith($request_uri, $pre)) {
                return 9;  // 请求 uri 在排除列表中  直接通过
            }
        }

        if (!empty($throttle['whitelist']) && in_array($ip, $throttle['whitelist'])) {
            return 8;  // 在白名单中  直接通过
        }
        if (!empty($throttle['blacklist']) && in_array($ip, $throttle['blacklist'])) {
            return -8;  // 在黑名单中  直接 封杀
        }

        $now = time();
        $per_day = date('Ymd', $now);
        $acc_seq = !empty($throttle['acc_seq']) ? $throttle['acc_seq'] : [60 => 0];
        $refuse_sec = isset($throttle['refuse_sec']) ? $throttle['refuse_sec'] : self::DEFAULT_REFUSE_SEC;
        $alive_sec = isset($throttle['alive_sec']) ? $throttle['alive_sec'] : self::DEFAULT_ALIVE_SEC;

        $refuseKey = "{$pre_key}:rip:{$ip}";
        if ($refuse_sec > 0 && !empty($yac)) {
            $t = $yac->get($refuseKey);
            if (!empty($t)) {
                if ($now > $t) {
                    $yac->delete($refuseKey);
                    return 7;  // 超出了屏蔽时间 通过
                } else {
                    return -7;  // 屏蔽时间之内 继续封杀
                }
            }
        }

        $skipInc = false;
        $aliveKey = "{$pre_key}:aip:{$ip}";
        if ($alive_sec > 0 && !empty($yac)) {
            $t = $yac->get($aliveKey);
            if (!empty($t)) {
                if ($now > $t) {  // 距离上次访问的时间 大于 $alive_sec
                    $skipInc = true;
                }
                // else  距离上次访问的时间 小于 $alive_sec  认为该 ip 有可能有风险 尝试进行计数
            } else {
                // 第一次访问 或者上次访问 key 失效  认为该 ip 为正常 ip 跳过计数
                $skipInc = true;
            }
            $yac->set($aliveKey, $now + $alive_sec, $alive_sec);
        }

        if ($skipInc) {
            return 2;  // 跳过 ip 计数 直接通过 通过
        }

        $mRedis = CacheTrait::_getRedisInstance();
        if (empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not checkPassIpThrottle  mRedis by _getRedisInstance <4>');
            return 4;  // 无法初始化 redis  直接通过
        }

        if ($refuse_sec > 0) {
            $t = $mRedis->get($refuseKey);
            if (!empty($t)) {
                if ($now > $t) {
                    $mRedis->delete($refuseKey);
                    !empty($yac) && $yac->delete($refuseKey);
                    !empty($yac) && $yac->delete($aliveKey);
                    $log_msg = "freeIp ip:{$ip}, t:{$t}, refuse_sec:{$refuse_sec}, per_day:{$per_day} <6>";
                    LogTrait::info($log_msg, __METHOD__, __CLASS__, __LINE__);
                    return 6;  // 超出了屏蔽时间 通过
                } else {
                    !empty($yac) && $yac->set($refuseKey, $t, 3600 + $refuse_sec);  // yac 中 同步设置一个 $refuseKey 提升下次屏蔽的 效率
                    !empty($yac) && $yac->set($aliveKey, $t, 3600 + $refuse_sec);  // 设置 yac 中 $aliveKey 为 拒绝时间  防止 yac 跳过计数
                    return -6;  // 屏蔽时间之内 继续封杀
                }
            }
        }

        $zsetKey = "{$pre_key}:lip:{$per_day}";
        $idx = 0;
        foreach ($acc_seq as $seq => $limit) {
            $timer_count = intval($now / $seq);
            $sKey = "{$pre_key}:ip:{$ip}:{$timer_count}";
            $num = $mRedis->incr($sKey);
            $num <= 1 && $mRedis->expire($sKey, $seq + 60);
            if ($idx == self::DEFAULT_RECORD_INDEX) {
                $score = intval($now / self::DEFAULT_SCORE_BASE) * self::DEFAULT_SCORE_BASE + $num;
                $mRedis->zAdd($zsetKey, $score, $ip);
                $num <= 1 && $mRedis->expire($zsetKey, 3 * 24 * 3600);
            }

            if ($limit > 0 && $num > $limit) {
                if ($refuse_sec > 0) {   // 如果设置了 屏蔽时间  添加该ip到屏蔽列表  不依靠有效期  因为 redis 可能是集群
                    $mRedis->setex($refuseKey, 3600 + $refuse_sec, $now + $refuse_sec);
                    $mRedis->zAdd($zsetKey, $now + $refuse_sec, $ip);  // 设置一个比较大的  score  方便检索查看
                    !empty($yac) && $yac->set($refuseKey, $now + $refuse_sec, 3600 + $refuse_sec);  // yac 中 同步设置一个 $refuseKey 提升下次屏蔽的 效率
                    !empty($yac) && $yac->set($aliveKey, $now + $refuse_sec, 3600 + $refuse_sec);  // 设置 yac 中 $aliveKey 为 拒绝时间  防止 yac 跳过计数
                }

                $log_msg = "overLimit ip:{$ip}, num:{$num}, seq:{$seq}, limit:{$limit}, refuse_sec:{$refuse_sec}, per_day:{$per_day} <-5>";
                LogTrait::info($log_msg, __METHOD__, __CLASS__, __LINE__);
                return -5;  // 超出了 单位时间内的 最大访问次数  拒绝本次访问
            }
            $idx++;
        }
        return 1;
    }

    private static function mergeIpSetting($origin_config, $config, array $list_keys = ['skip_pre', 'blacklist', 'whitelist'])
    {
        foreach ($list_keys as $list_key) {
            $origin_list = Util::v($origin_config, $list_key, []);
            $list = Util::v($config, $list_key, []);
            $tmp = Util::build_map_set(array_merge($origin_list, $list));
            $config[$list_key] = $tmp;
        }
        return array_merge($origin_config, $config);
    }

    public static function loadIpSetting($pre_key, $origin_throttle)
    {
        $mRedis = CacheTrait::_getRedisInstance();
        if (empty($pre_key) || empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not loadIpSetting  mRedis by _getRedisInstance ');
            return [];
        }
        $settingKey = "{$pre_key}:setting";
        $json_str = $mRedis->get($settingKey);
        $json = !empty($json_str) ? json_decode($json_str, true) : [];
        return array_merge($origin_throttle, $json);
    }

    public static function saveIpSetting($pre_key, $throttle)
    {
        $mRedis = CacheTrait::_getRedisInstance();
        if (empty($pre_key) || empty($throttle) || empty($mRedis) || $mRedis instanceof EmptyMock) {
            error_log(__METHOD__ . ' can not saveIpSetting  mRedis by _getRedisInstance ');
            return [];
        }
        $settingKey = "{$pre_key}:setting";
        $json_str = json_encode($throttle);
        $mRedis->set($settingKey, $json_str);
        return $throttle;
    }

}