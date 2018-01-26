<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/26 0026
 * Time: 11:38
 */

namespace Tiny\Exception;


use Exception;

class AbortError extends Error
{
    protected static $errno = 500;

    public function __construct($code = 500, $message = '', Exception $previous = null)
    {
        // 可用 错误码 范围 [400, 600)
        static::$errno = ($code >= 400 && $code < 600) ? intval($code) : 500;
        parent::__construct($message, $previous);
    }
}