<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/23 0023
 * Time: 10:38
 */

namespace Tiny\Interfaces;


interface EventInterface
{

    public function getType();

    public function getParams();

    public function getObject();

}