<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/23 0023
 * Time: 11:34
 */

namespace Tiny\Event;


use Tiny\Abstracts\AbstractController;
use Tiny\Abstracts\AbstractEvent;
use Tiny\Util;

class ControllerEvent extends AbstractEvent
{

    /**
     * ControllerEvent constructor.
     * @param string $type
     * @param AbstractController $object
     * @param string $view_file
     * @param array $view_args
     */
    public function __construct($type, AbstractController $object, $view_file, array $view_args = [])
    {
        parent::__construct($type, $object, [
            'view_file' => $view_file,
            'view_args' => $view_args,
        ]);
    }

    /**
     * @return AbstractController
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * @return string
     */
    public function getViewFile()
    {
        return Util::v($this->_params, 'view_file', '');
    }

    /**
     * @return array
     */
    public function getViewArgs()
    {
        return Util::v($this->_params, 'view_args', []);
    }
}