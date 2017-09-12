<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/7
 * Time: 14:02
 */

namespace Tiny\Plugin\graphiql\controller;


use Tiny\Func;
use Tiny\Plugin\graphiql\base\BaseGraphiQLController;

class assets extends BaseGraphiQLController
{

    public function index()
    {
        $uri = $this->getRequest()->getRequestUri();
        $tmp_list = explode('/', $uri);
        $file_name = $tmp_list[count($tmp_list) - 1];
        $routeInfo = $this->getRequest()->getRouteInfo();
        $file_path = Func::joinNotEmpty(DIRECTORY_SEPARATOR, [static::$template_dir, $routeInfo[0], $routeInfo[1], $file_name]);
        $this->sendFile($file_path);
    }

}