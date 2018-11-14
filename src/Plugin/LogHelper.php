<?php

namespace Tiny\Plugin;

/*
 *  日志辅助类  需要 LOG_PATH 常量 指定日志存放目录 LOG_LEVEL 表示记录级别的字符串
 *  日志记录器(Logger)是日志处理的核心组件。log4j具有5种正常级别(Level)。:
 *  1.static Level DEBUG :
 *  DEBUG Level指出细粒度信息事件对调试应用程序是非常有帮助的。
 *  2.static Level INFO
 *  INFO level表明 消息在粗粒度级别上突出强调应用程序的运行过程。
 *  3.static Level WARN
 *  WARN level表明会出现潜在错误的情形。
 *  4.static Level ERROR
 *  ERROR level指出虽然发生错误事件，但仍然不影响系统的继续运行。
 *  5.static Level FATAL
 *  FATAL level指出每个严重的错误事件将会导致应用程序的退出。
 *
 *  另外，还有两个可用的特别的日志记录级别:
 *  1.static Level ALL
 *  ALL Level是最低等级的，用于打开所有日志记录。
 *  2.static Level OFF
 *  OFF Level是最高等级的，用于关闭所有日志记录。
*/
use app\Util;
use Tiny\Application;

class LogHelper
{

    private static $log_level_dict = [
        'ALL' => 0,
        'DEBUG' => 10,
        'INFO' => 20,
        'WARN' => 30,
        'ERROR' => 40,
        'FATAL' => 50,
        'OFF' => 60,
    ];
    private static $m_instance_dict = [];

    private $_log_path = '';
    private $_log_level = 'INFO';
    private $_module = 'base_log';

    /**
     * 单实例模式
     * @param string $module
     * @return LogHelper
     */
    public static function create($module = 'sys_log')
    {
        if (isset(self::$m_instance_dict[$module]) || !empty(self::$m_instance_dict[$module])) {
            return self::$m_instance_dict[$module];
        } else {
            self::$m_instance_dict[$module] = new self($module, null, null);
            return self::$m_instance_dict[$module];
        }
    }

    public function __construct($_module = 'sys_log', $_log_path = null, $_log_level = null)
    {
        $this->_module = $_module;
        $log_config = Application::app()->config('ENV_LOG');
        $this->_log_path = !empty($log_config['path']) ? $log_config['path'] : '';
        $this->_log_level = !empty($log_config['level']) ? $log_config['level'] : '';

        if (!is_null($_log_path)) {
            $this->_log_path = $_log_path;
        }
        if (!is_null($_log_level)) {
            $this->_log_level = $_log_level;
        }
    }

    public function debug($content)
    {
        return $this->writeLog($content, 'DEBUG');
    }

    public function info($content)
    {
        return $this->writeLog($content, 'INFO');
    }

    public function warn($content)
    {
        return $this->writeLog($content, 'WARN');
    }

    public function error($content)
    {
        return $this->writeLog($content, 'ERROR');
    }

    public function fatal($content)
    {
        return $this->writeLog($content, 'FATAL');
    }

    /**
     * 以指定级别写日志
     * @param string $content
     * @param string $type
     * @return string
     */
    public function writeLog($content, $type = 'INFO')
    {
        if (empty($this->_log_path) || empty($this->_log_level)) {
            return '';
        }
        $type = strtoupper($type);
        $level = isset(self::$log_level_dict[$type]) ? self::$log_level_dict[$type] : -1;
        $level = ($level >= 10 && $level <= 50) ? $level : -10;
        $level_need = isset(self::$log_level_dict[$this->_log_level]) ? self::$log_level_dict[$this->_log_level] : 30;  //未指定日志级别时只记录WARN及以上信息
        if ($level < $level_need) {  //级别低于当前级别直接返回空字符串
            return '';
        }

        $logPath = (!Util::str_endwith($this->_log_path, '/') ? $this->_log_path . '/' : $this->_log_path) . $this->_module;

        if (!is_dir($logPath)) {
            mkdir($logPath, 0777, true);
            chmod($logPath, 0777);
        }
        $file = $logPath . '/' . date('Y-m-d') . '.log';
        $content = date('Y-m-d H:i:s') . " [{$type}] {$content}\n";
        file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
        return $content;
    }

    /**
     * 遍历文件夹 得到目录结构
     * @param string $path
     * @param string $base_path
     * @return array
     */
    public static function getLogPathArray($path = '', $base_path = '')
    {
        if (empty($path)) {
            $log_config = Application::config('ENV_LOG');
            $log_path = !empty($log_config['path']) ? $log_config['path'] : '';
            if (empty($log_path)) {
                return [];
            }
            $path = $log_path;
            $base_path = $log_path;
        }
        if (!is_dir($path) || !is_readable($path)) {
            return [];
        }

        $result = [];
        $temp = [];
        $allfiles = scandir($path);  //获取目录下所有文件与文件夹 
        foreach ($allfiles as $key => $filename) {  //遍历一遍目录下的文件与文件夹 
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            $fullname = $path . '/' . $filename;  //得到完整文件路径
            $file_item = [
                'name' => $filename,
                'fullname' => $fullname,
                'ctime' => filectime($fullname),
                'mtime' => filemtime($fullname),
                'path' => str_replace($base_path, '', $fullname),
            ];
            if (is_dir($fullname)) { //是目录的话继续递归
                $file_item['type'] = 'dir';
                $file_item['sub'] = self::getLogPathArray($fullname, $base_path);
                $file_item['size'] = 0;
                foreach ($file_item['sub'] as $k => $v) {
                    $file_item['size'] += $v['size'];
                }
                $result[] = $file_item;
            } else if (is_file($fullname)) {
                $file_item['type'] = 'file';
                $file_item['size'] = filesize($fullname);
                $temp[] = $file_item;
            }
        }

        foreach ($temp as $key => $tmp) {
            $result[] = $tmp; //这样可以让文件夹排前面，文件在后面 
        }
        return $result;
    }

    /**
     * 读取指定日志文件内容
     * @param $path
     * @return string
     */
    public static function readLogByPath($path, $max_mb = 5)
    {
        $log_config = Application::config('ENV_LOG');
        $log_path = !empty($log_config['path']) ? $log_config['path'] : '';
        if (empty($log_path)) {
            return '';
        }

        $file = $log_path . $path;
        if (strpos($file, '..') !== false || empty($path)) {
            return '';  //防止恶意访问 只允许访问log文件夹下文件
        }
        if (is_file($file)) {
            $file_str = filesize($file) > $max_mb * 1024 * 1024 ? "file gt {$max_mb} MB" : file_get_contents($file);
            return $file_str;
        } else {
            return '';
        }
    }

    /**
     * 清空当前文件内容 把原日志文件备份 只可操作 今天的日志
     * @param $path
     * @return bool
     */
    public static function clearLogByPath($path)
    {
        $log_config = Application::config('ENV_LOG');
        $log_path = !empty($log_config['path']) ? $log_config['path'] : '';
        if (empty($log_path)) {
            return '';
        }

        $file = $log_path . $path;
        if (strpos($file, '..') > 0 || empty($path)) {
            return false;  //防止恶意访问 只允许访问log文件夹下文件
        }
        if (is_file($file)) {
            $new_path = $path . '.' . time();
            $content = date('Y-m-d H:i:s') . " [WARN] superAdmin cut this file by syslog, rename file：{$new_path}\n";
            file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
            $test = @rename($file, $log_path . $new_path) ? true : false;
            if ($test) {
                $content = date('Y-m-d H:i:s') . " [WARN] superAdmin cut last file by syslog, last file：{$new_path}\n";
                file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
            }
            return $test;
        } else {
            return false;
        }
    }

} 