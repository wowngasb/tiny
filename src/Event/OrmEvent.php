<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/23 0023
 * Time: 11:05
 */

namespace Tiny\Event;


use Tiny\Abstracts\AbstractEvent;
use Tiny\Traits\OrmConfig;
use Tiny\Util;

class OrmEvent extends AbstractEvent
{

    /**
     * OrmEvent constructor.
     * @param string $type
     * @param OrmConfig $object
     * @param string $sql
     * @param array $view_args
     * @param int $time
     * @param string $tag
     */
    public function __construct($type, OrmConfig $object, $sql, array $view_args = [], $time = 0, $tag = '')
    {
        parent::__construct($type, $object, [
            'sql' => $sql,
            'args' => $view_args,
            'time' => $time,
            'tag' => $tag,
        ]);
    }

    /**
     * @return OrmConfig
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return Util::v($this->_params, 'sql', '');
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return Util::v($this->_params, 'args', []);
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return Util::v($this->_params, 'time', 0);
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return Util::v($this->_params, 'tag', '');
    }
}