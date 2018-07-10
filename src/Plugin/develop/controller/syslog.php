<?php

namespace Tiny\Plugin\develop\controller;


use Tiny\Application;
use Tiny\Util;
use Tiny\Plugin\ApiHelper;
use Tiny\Plugin\develop\DevelopController;
use Tiny\Plugin\LogHelper;

class sysLog extends DevelopController
{

    public function beforeAction(array $params)
    {
        $params = parent::beforeAction($params);

        if (!$this->authDevelopKey()) {  //认证 不通过
            Application::redirect($this->getResponse(), Application::url($this->getRequest(), ['', 'index', 'index']));
        }
        return $params;
    }

    public function index()
    {
        Application::forward($this->getRequest(), $this->getResponse(), ['', '', 'showlogdir']);
    }

    public function showLogDir()
    {
        $arr_dir = LogHelper::getLogPathArray();
        $arr_dir = $this->fixPathData($arr_dir);
        $json_dir = json_encode($arr_dir);

        $this->assign('tool_title', '后台日志查看系统');
        $this->assign('json_dir', $json_dir);
        $this->display();
    }

    public function showLogFile()
    {
        $path = $this->_get('file', '');
        $this->assign('tool_title', $path);

        $file_str = LogHelper::readLogByPath($path);
        $this->assign('file_str', $file_str);

        $color_type = $this->_get('color_type', 'tagcolor');
        $color_type = strlen($file_str) > 1024 * 1024 ? 'default' : $color_type;
        $this->assign('color_type', $color_type);

        $scroll_to = $this->_get('scroll_to', 'end');
        $this->assign('scroll_to', $scroll_to);
        $this->display();
    }

    public function ajaxClearLogFile()
    {
        $path = $this->_get('file', '');
        if (empty($path)) {
            $result = ['errno' => -1, 'msg' => "参数错误"];
            return $this->getResponse()->appendBody(json_encode($result));
        }
        $test = pathinfo($path);
        if ($test['filename'] != date('Y-m-d')) {
            $result = ['errno' => -2, 'msg' => "不可清空今日以前日志"];
            return $this->getResponse()->appendBody(json_encode($result));
        }

        $rst = LogHelper::clearLogByPath($path);
        if ($rst) {
            $result = ['errno' => 0, 'msg' => "{$path}已清空"];
        } else {
            $result = ['errno' => -3, 'msg' => "{$path}清空失败"];
        }
        return $this->getResponse()->appendBody(json_encode($result));
    }

    public function downLoadLogFile()
    {
        $path = $this->_get('file', '');
        $file_str = LogHelper::readLogByPath($path, 100);
        $file_name = str_replace('/', '_', $path);
        $file_name = substr($file_name, 1);
        header("Content-type:text/log");
        header("Content-Disposition:attachment;filename=" . $file_name);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $this->getResponse()->appendBody($file_str);
    }

    private function fixPathData($arr_dir)
    {
        $rst = [];
        foreach ($arr_dir as $key => $val) {
            $val['ctime_str'] = date('Y-m-d H:i:s', $val['ctime']);
            $val['mtime_str'] = date('Y-m-d H:i:s', $val['mtime']);
            $val['size_str'] = Util::byte2size($val['size']) . 'B';
            if ($val['type'] == 'file') {
                $rst[] = [
                    'text' => $val['name'],
                    'id' => $val['name'],
                    'href' => Application::url($this->getRequest(), ['', '', 'showlogfile'], ['file' => $val['path'], 'scroll_to' => 'end']),
                    'leaf' => true,
                    'file_info' => "create_time : {$val['ctime_str']}, modify_time : {$val['mtime_str']}, size : {$val['size_str']}",
                ];
            } else if ($val['type'] == 'dir') {
                $val['sub'] = isset($val['sub']) ? $val['sub'] : [];
                $rst[] = [
                    'text' => $val['name'],
                    'id' => $val['name'],
                    'href' => '',
                    'leaf' => false,
                    'expanded' => false,
                    'children' => $this->fixPathData($val['sub']),
                    'file_info' => "create_time : {$val['ctime_str']}, modify_time : {$val['mtime_str']}, size : {$val['size_str']}",
                ];
            }
        }
        return $rst;
    }

    /**
     *
     */
    public function selectApi()
    {
        $appname = Application::appname();
        $api_path = Application::path([$appname, 'api']);
        $api_list = ApiHelper::getApiFileList($api_path);
        $tmp = [];
        foreach ($api_list as $key => $val) {
            $cls = str_replace('.php', '', $val['name']);
            $tmp[] = [
                'id' => $cls,
                'text' => $cls,
                'leaf' => false,
            ];
        }
        usort($tmp, function ($a, $b) {
            return $a > $b ? 1 : -1;
        });
        $json_api_list = json_encode($tmp);
        $this->assign('json_api_list', $json_api_list);
        $this->assign('tool_title', '后台API调试系统');
        $this->display();
    }

    public function getParamList()
    {
        $appname = Application::appname();
        $args_list = [];
        $note = '';
        $class = $this->_get('cls', '');
        $method = $this->_get('method', '');
        if (!empty($class) && !empty($method)) {
            $class_name = "\\{$appname}\\api\\{$class}";
            $args_list = ApiHelper::getApiParamList($class_name, $method);
            $note = ApiHelper::getApiNoteStr($class_name, $method);
        }
        $rst['Args'] = $args_list;
        $rst['Note'] = $note;
        $this->getResponse()->appendBody(json_encode($rst));
    }

    public function getMethodList()
    {
        $appname = Application::appname();
        $tmp = [];
        $class = $this->_get('id', '');
        $method_list = [];
        if (!empty($class)) {
            $class_name = "\\{$appname}\\api\\{$class}";
            $method_list = ApiHelper::getApiMethodList($class_name);
        }
        foreach ($method_list as $key => $val) {
            $name = $val['name'];
            if ($name == '__construct' || strpos($name, 'hook', 0) === 0) {
                continue;
            }
            $tmp[] = [
                'id' => $name,
                'text' => $name,
                'leaf' => true,
            ];
        }
        usort($tmp, function ($a, $b) {
            return $a > $b ? 1 : -1;
        });
        $this->getResponse()->appendBody(json_encode($tmp));
    }

}
