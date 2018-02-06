<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/23 0023
 * Time: 10:54
 */

namespace Tiny\Abstracts;


use Tiny\Interfaces\EventInterface;

abstract class AbstractEvent implements EventInterface
{


    protected $_object = null;
    protected $_params = [];
    protected $_type = '';

    /**
     * HttpEvent constructor.
     * @param string $type
     * @param mixed $object
     * @param array $params
     */
    public function __construct($type, $object, array $params)
    {
        $this->_type = $type;
        $this->_object = $object;
        $this->_params = $params;
    }

    /**
     * @return mixed
     */
    abstract public function getObject();

    /* public function getObject(){
        return $this->_object;
    } */

    public function getType()
    {
        return $this->_type;
    }

    public function getParams()
    {
        return $this->_params;
    }

}