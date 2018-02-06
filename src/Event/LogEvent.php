<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/2/2 0002
 * Time: 9:46
 */

namespace Tiny\Event;


use app\Util;
use Tiny\Abstracts\AbstractEvent;

class LogEvent extends AbstractEvent
{

    public function __construct($type, $msg, $method, $class, $line_no)
    {
        $params = [
            'msg' => $msg,
            'method' => $method,
            'class' => $class,
            'line_no' => $line_no,
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
     * @return string
     */
    public function getMethod()
    {
        return Util::v($this->_params, 'method', '');
    }

    /**
     * @return string
     */
    public function getMsg()
    {
        return Util::v($this->_params, 'msg', '');
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return Util::v($this->_params, 'class', '');
    }

    /**
     * @return int
     */
    public function getLineNo()
    {
        return Util::v($this->_params, 'line_no', 0);
    }

}