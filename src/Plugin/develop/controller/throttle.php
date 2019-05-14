<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/11/14 0014
 * Time: 19:13
 */

namespace Tiny\Plugin\develop\controller;


use Tiny\Application;
use Tiny\Plugin\develop\DevelopController;
use Tiny\Plugin\ThrottleHelper;

class throttle extends DevelopController
{

    public function beforeAction(array $params)
    {
        $params = parent::beforeAction($params);

        if (!self::authDevelopKey($this->getRequest())) {  //认证 不通过
            Application::redirect($this->getResponse(), Application::url($this->getRequest(), ['', 'index', 'index']));
        }
        return $params;
    }

    public function index()
    {
        Application::forward($this->getRequest(), $this->getResponse(), ['', '', 'showState']);
    }

    public function showState()
    {
        $app = Application::app();
        $throttle = $app::config('services.throttle', []);
        $default_pre_key = !empty($throttle['pre_key']) ? $throttle['pre_key'] : ThrottleHelper::DEFAULT_PRE_KEY;
        $acc_seq = !empty($throttle['acc_seq']) ? $throttle['acc_seq'] : [60 => 0];
        $record_index = ThrottleHelper::DEFAULT_RECORD_INDEX;

        $acc_seq_list = [];
        foreach ($acc_seq as $seq => $limit) {
            $acc_seq_list[] = [
                'seq' => $seq,
                'limit' => $limit,
            ];
        }

        $this->assign('default_pre_key', $default_pre_key);
        $this->assign('acc_seq_list', $acc_seq_list);
        $this->assign('record_index', $record_index);
        $this->display();
    }

    public function apiIpSetting($pre_key = '')
    {
        $pre_key = trim($pre_key);

        $app = Application::app();
        $origin_throttle = $app::config('services.throttle', []);
        $default_pre_key = !empty($origin_throttle['pre_key']) ? $origin_throttle['pre_key'] : ThrottleHelper::DEFAULT_PRE_KEY;
        $pre_key = !empty($pre_key) ? $pre_key : $default_pre_key;

        $ret = [
            'code' => 0,
            'msg' => '获取成功',
            'pre_key' => $pre_key,
            'throttle' => ThrottleHelper::loadIpSetting($pre_key, $origin_throttle),
            'throttle_origin' => $origin_throttle,
        ];
        return $this->getResponse()->json($ret);
    }

    public function apiSaveIpSetting($pre_key = '', $throttle = [])
    {
        $pre_key = trim($pre_key);

        $app = Application::app();
        $origin_throttle = $app::config('services.throttle', []);
        $default_pre_key = !empty($origin_throttle['pre_key']) ? $origin_throttle['pre_key'] : ThrottleHelper::DEFAULT_PRE_KEY;
        $pre_key = !empty($pre_key) ? $pre_key : $default_pre_key;

        if (isset($throttle['enable'])) {
            $throttle['enable'] = intval($throttle['enable']);
        }
        if (isset($throttle['refuse_sec'])) {
            $throttle['refuse_sec'] = intval($throttle['refuse_sec']);
        }
        if (isset($throttle['alive_sec'])) {
            $throttle['alive_sec'] = intval($throttle['alive_sec']);
        }
        if (isset($throttle['acc_seq_json'])) {
            $throttle['acc_seq'] = json_decode($throttle['acc_seq_json'], true);
            unset($throttle['acc_seq_json']);
        }

        $ret = [
            'code' => 0,
            'msg' => '设置成功',
            'pre_key' => $pre_key,
            'throttle' => ThrottleHelper::saveIpSetting($pre_key, array_merge($origin_throttle, $throttle)),
        ];
        return $this->getResponse()->json($ret);
    }

    public function apiIpList($pre_key = '', $per_day = 0, $ip = '', $page = 1, $num = 50)
    {
        $per_day = !empty($per_day) ? intval($per_day) : date('Ymd');
        $page = $page > 1 ? intval($page) : 1;
        $num = $num > 10 ? intval($num) : 50;
        $pre_key = trim($pre_key);

        $app = Application::app();
        $throttle = $app::config('services.throttle', []);
        $default_pre_key = !empty($throttle['pre_key']) ? $throttle['pre_key'] : ThrottleHelper::DEFAULT_PRE_KEY;
        $pre_key = !empty($pre_key) ? $pre_key : $default_pre_key;
        $acc_seq = !empty($throttle['acc_seq']) ? $throttle['acc_seq'] : [60 => 0];

        $ipList = ThrottleHelper::loadIpList($pre_key, $acc_seq, $per_day, $ip, $page, $num);
        $total = !empty($ip) ? count($ipList) : ThrottleHelper::countIpList($pre_key, $per_day);
        $ret = [
            'code' => 0,
            'msg' => '获取成功',
            'ipList' => !empty($ipList) ? $ipList : [],
            'pageInfo' => [
                'total' => $total,
                'page' => $page,
                'num' => $num,
                'sortOption' => [
                    'field' => 'num',
                    'direction' => 'DESC'
                ]
            ]
        ];
        return $this->getResponse()->json($ret);
    }

}