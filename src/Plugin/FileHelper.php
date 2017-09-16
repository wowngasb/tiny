<?php

namespace Tiny\Plugin;


class FileHelper
{

    /**
     * safe_file_put_contents() 一次性完成打开文件，写入内容，关闭文件三项工作，并且确保写入时不会造成并发冲突
     * @param string $filename
     * @param string $content
     * @return boolean
     */
    public static function safe_file_put_contents($filename, $content)
    {
        if (empty($filename) || empty($content)) {
            return false;
        }
        $fp = fopen($filename, 'wb');
        if (!$fp) {
            return false;
        }
        flock($fp, LOCK_EX);
        fwrite($fp, $content);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

    /**
     * safe_file_get_contents() 用共享锁模式打开文件并读取内容，可以避免在并发写入造成的读取不完整问题
     * @param string $filename
     * @return string|null
     */
    public static function safe_file_get_contents($filename)
    {
        if (empty($filename)) {
            return null;
        }
        $fp = fopen($filename, 'rb');
        if (!$fp) {
            return null;
        }
        $data = '';
        flock($fp, LOCK_SH);
        clearstatcache();
        $size = filesize($filename);
        if ($size > 0) {
            $data = fread($fp, $size);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        return $data;
    }

} 