<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/27 0027
 * Time: 21:28
 */

namespace Tiny\Traits;


use Tiny\Application;
use Tiny\Exception\AppStartUpError;

trait EncryptTrait
{

    /**
     * @return string
     * @throws AppStartUpError
     */
    protected static function _getSalt()
    {
        throw new AppStartUpError("must overwrite method EncryptTrait::_getSalt() :string");
    }


    public static function _encode($str, $expiry = 0, $salt = '')
    {
        return Application::encrypt($str, $expiry, static::_getSalt() . "{$salt}");
    }

    public static function _decode($token, $salt = '')
    {
        return Application::decrypt($token, static::_getSalt() . "{$salt}");
    }

}