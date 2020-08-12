<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/2/2 0002
 * Time: 9:46
 */

namespace Tiny\Event;


use Tiny\Abstracts\AbstractEvent;
use Tiny\Util;

class CacheEvent extends AbstractEvent
{

    public function __construct($type, $now, $method, $key, $timeCache, $update, $tags = [], $useStatic = false, $useYac = false, $bytes = 0)
    {
        $params = [
            'now' => $now,
            'method' => $method,
            'key' => $key,
            'timeCache' => $timeCache,
            'update' => $update,
            'tags' => $tags,
            'useStatic' => $useStatic,
            'useYac' => $useYac,
            'bytes' => $bytes,
        ];
        parent::__construct($type, null, $params);
    }

    /**
     * @return null
     */
    public function getObject()
    {
        return null;
    }


    /**
     * @return bool
     */
    public function isUseStatic()
    {
        return Util::v($this->_params, 'useStatic', false);
    }

    /**
     * @return bool
     */
    public function isUseYac()
    {
        return Util::v($this->_params, 'useYac', false);
    }

    /**
     * @return int
     */
    public function getNow()
    {
        return Util::v($this->_params, 'now', 0);
    }

    /**
     * @return int
     */
    public function getBytes()
    {
        return Util::v($this->_params, 'bytes', 0);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return Util::v($this->_params, 'method', '');
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return Util::v($this->_params, 'key', '');
    }

    /**
     * @return int
     */
    public function getTimeCache()
    {
        return Util::v($this->_params, 'timeCache', 0);
    }


    /**
     * @return int
     */
    public function getUpdate()
    {
        return Util::v($this->_params, 'update', 0);
    }


    /**
     * @return array
     */
    public function getTags()
    {
        return Util::v($this->_params, 'tags', []);
    }
}