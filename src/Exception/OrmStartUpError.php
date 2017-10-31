<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/25 0025
 * Time: 11:36
 */

namespace Tiny\Exception;


class OrmStartUpError extends Error
{
    protected static $errno = 512;

}