<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/24 0024
 * Time: 19:43
 */

namespace Tiny\Exception;


class AuthError extends Error
{
    protected static $errno = 403;

}