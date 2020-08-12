<?php


namespace Tiny\Plugin;


class MyYac
{
    private $_yac = null;

    public function __construct($_yac)
    {
        $this->_yac = $_yac;
    }

    public function get($key)
    {
        $key = trim("$key");
        $_key = !empty($key) && strlen($key) > 32 ? md5($key) : $key;
        return !empty($this->_yac) && !empty($_key) ? $this->_yac->get($_key) : null;
    }

    public function setex($key, $ttl, $value)
    {
        $ttl = $ttl > 0 ? intval($ttl) : 0;
        $key = trim("$key");
        $_key = !empty($key) && strlen($key) > 32 ? md5($key) : $key;
        return !empty($this->_yac) && !empty($_key) ? $this->_yac->set($_key, $value, $ttl) : false;
    }

    public function mget($keys)
    {
        $keys = !is_array($keys) ? [$keys] : $keys;
        $_keys = [];
        foreach ($keys as $key) {
            $key = trim("$key");
            $_keys[] = strlen($key) > 32 ? md5($key) : $key;
        }
        $retMap = !empty($this->_yac) && !empty($_keys) ? $this->_yac->get($_keys) : [];
        $ret = [];
        foreach ($_keys as $_key) {
            $val = isset($retMap[$_key]) && $retMap[$_key] !== false ? $retMap[$_key] : null;
            $ret[] = $val;
        }
        return $ret;
    }

    public function del($keys)
    {
        $keys = !is_array($keys) ? [$keys] : $keys;
        $_keys = [];
        foreach ($keys as $key) {
            $key = trim("$key");
            $_keys[] = strlen($key) > 32 ? md5($key) : $key;
        }
        return !empty($this->_yac) && !empty($keys) ? $this->_yac->delete(count($_keys) == 1 ? $_keys[0] : $_keys) : false;
    }

}