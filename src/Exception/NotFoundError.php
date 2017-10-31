<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/29 0029
 * Time: 3:08
 */

namespace Tiny\Exception;


final class NotFoundError extends Error
{
    protected static $errno = 404;

}