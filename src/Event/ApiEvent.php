<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/23 0023
 * Time: 10:52
 */

namespace Tiny\Event;


use Exception;
use Tiny\Abstracts\AbstractApi;
use Tiny\Abstracts\AbstractEvent;
use Tiny\Util;

class ApiEvent extends AbstractEvent
{

    /**
     * ApiEvent constructor.
     * @param string $type
     * @param AbstractApi $object
     * @param string $action
     * @param array $view_args
     * @param array $result
     * @param Exception $exception
     * @param string $callback
     */
    public function __construct($type, AbstractApi $object, $action, array $view_args = [], array $result = [], Exception $exception = null, $callback = '')
    {
        parent::__construct($type, $object, [
            'action' => $action,
            'args' => $view_args,
            'result' => $result,
            'exception' => $exception,
            'callback' => $callback,
        ]);
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return Util::v($this->_params, 'action', '');
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return Util::v($this->_params, 'args', []);
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return Util::v($this->_params, 'result', []);
    }

    /**
     * @return Exception
     */
    public function getException()
    {
        return Util::v($this->_params, 'exception', null);
    }

    /**
     * @return string
     */
    public function getCallback()
    {
        return Util::v($this->_params, 'callback', '');
    }

    /**
     * @return AbstractApi
     */
    public function getObject()
    {
        return $this->_object;
    }
}