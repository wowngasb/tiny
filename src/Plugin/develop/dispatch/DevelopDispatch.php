<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/8/14
 * Time: 12:27
 */

namespace Tiny\Plugin\develop\dispatch;


use Tiny\Abstracts\AbstractDispatch;
use Tiny\Util;

class DevelopDispatch extends AbstractDispatch
{

    /**
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodNamespace(array $routeInfo)
    {
        $controller = !empty($routeInfo[1]) ? trim($routeInfo[1]) : 'index';
        $module = !empty($routeInfo[0]) ? trim($routeInfo[0]) : 'develop';

        return "\\Tiny\\Plugin\\{$module}\\controller\\{$controller}";
    }

    public static function initMethodName(array $routeInfo)
    {
        $file_name = !empty($routeInfo[2]) ? trim($routeInfo[2]) : 'index';
        return Util::stri_cmp($routeInfo[1], 'assets') ? 'index' : $file_name;
    }

}