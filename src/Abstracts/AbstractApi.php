<?php

namespace Tiny\Abstracts;


use Tiny\Event\ApiEvent;
use Tiny\Util;

abstract class AbstractApi extends AbstractContext
{

    protected static $_API_LIMIT_KET = 'BaseApiRateLimit';

    public function _param($name, $default = '')
    {
        $params = $this->getRequest()->getParams();
        return Util::v($params, $name, $default);
    }

    /*
     * 不同的API会有不同的调用次数限制, 请检查返回 header 中的如下字段
     * header 字段	描述
     */
    public function _apiLimitByTimeRange($api_key, $range_sec = 300, $max_num = 100, $tag = 'all')
    {
        $testRst = static::_apiLimitByTimeRangeTest($api_key, $range_sec, $max_num, $tag);
        foreach ($testRst as $key => $val) {
            $this->getResponse()->addHeader("X-Rate-{$key}: {$val}", true);
        }

        if (!empty($testRst['Remaining']) && $testRst['Remaining'] < 0) {
            $this->getResponse()->resetResponse()->addHeader("http/1.1 403 Forbidden", true)->setResponseCode(403)->end();
        }
        /*
        header("X-Rate-LimitTag: {$tag}");  //限制规则分类 all 代表总数限制
        header("X-Rate-LimitNum: {$max_num}");  //限制调用次数，超过后服务器会返回 403 错误
        header("X-Rate-Remaining: {$remaining}");  //当时间段中还剩下的调用次数
        header("X-Rate-TimeRange: {$range_sec}");  //限制时间范围长度 单位 秒
        header("X-Rate-TimeReset: {$reset_date}");  //限制重置时间 unix time
        */
    }

    /**
     * API 调用次数限制
     * @param $api_key
     * @param int $range_sec
     * @param int $max_num
     * @param string $tag
     * @return array
     */
    public static function _apiLimitByTimeRangeTest($api_key, $range_sec = 300, $max_num = 100, $tag = 'all')
    {
        $max_num = intval($max_num);
        $range_sec = intval($range_sec);
        $range_sec = $range_sec > 0 ? $range_sec : 1;
        $time_count = intval(time() / $range_sec);
        $max_num = $max_num > 0 ? $max_num : 1;
        $rKey = static::$_API_LIMIT_KET . ":{$api_key}:num_{$tag}_{$time_count}_{$range_sec}";

        $mCache = static::_getCacheInstance();  // 可以直接换成redis实现
        $tmp = $mCache->getItem($rKey)->get();
        $count = intval($tmp) > 0 ? intval($tmp) + 1 : 1;
        $itemObj = $mCache->getItem($rKey)->set($count)->expiresAfter(2 * $range_sec);  // 多保留一段时间
        $mCache->save($itemObj);

        return [
            'LimitTag' => $tag,
            'LimitNum' => $max_num,
            'Remaining' => $max_num - $count,
            'TimeRange' => $range_sec,
            'TimeReset' => gmdate('D, d M Y H:i:s T', ($time_count + 1) * $range_sec),
        ];
    }

    ###############################################################
    ############## 重写 EventTrait::isAllowedEvent ################
    ###############################################################

    /**
     *  注册回调函数  回调参数为 callback(\Tiny\Event\ApiEvent $event)
     *  1、apiResult    api执行完毕返回结果时触发
     *  2、apiException    api执行发生异常时触发
     * @param string $event
     * @return bool
     */
    protected static function isAllowedEvent($event)
    {
        static $allow_event = ['apiResult', 'apiException'];
        return in_array($event, $allow_event);
    }

    public function _doneApi($action, $params, $result, $callback)
    {
        static::fire(new ApiEvent('apiResult', $this, $action, $params, $result, null, $callback));
    }

    public function _exceptApi($action, $params, $exception, $callback)
    {
        static::fire(new ApiEvent('apiException', $this, $action, $params, [], $exception, $callback));
    }

}