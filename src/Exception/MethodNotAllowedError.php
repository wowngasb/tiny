<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/25 0025
 * Time: 13:38
 */

namespace Tiny\Exception;


class MethodNotAllowedError extends Error
{
    protected static $errno = 405;
}