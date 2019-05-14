<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/7
 * Time: 14:03
 */

namespace Tiny\Plugin\graphiql\dispatch;

use Tiny\Abstracts\AbstractDispatch;
use Tiny\Util;

class GraphiQLDispatch extends AbstractDispatch
{

    /**
     * @param array $routeInfo
     * @return string
     */
    public static function initMethodNamespace(array $routeInfo)
    {
        $controller = !empty($routeInfo[1]) ? trim($routeInfo[1]) : 'index';
        $module = !empty($routeInfo[0]) ? trim($routeInfo[0]) : 'graphiql';

        return "\\Tiny\\Plugin\\{$module}\\controller\\{$controller}";
    }

    public static function initMethodName(array $routeInfo)
    {
        $file_name = !empty($routeInfo[2]) ? trim($routeInfo[2]) : 'index';
        return Util::stri_cmp($routeInfo[1], 'assets') ? 'index' : $file_name;
    }

}