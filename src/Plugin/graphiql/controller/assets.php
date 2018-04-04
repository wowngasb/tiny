<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/7
 * Time: 14:02
 */

namespace Tiny\Plugin\graphiql\controller;


use Tiny\Plugin\graphiql\GraphiQLController;
use Tiny\Util;

class assets extends GraphiQLController
{

    public function index()
    {
        $uri = $this->getRequest()->getRequestUri();
        $tmp_list = explode('/', $uri);
        $file_name = $tmp_list[count($tmp_list) - 1];
        $file_name = explode('#', $file_name)[0];
        $file_name = explode('?', $file_name)[0];
        $routeInfo = $this->getRequest()->getRouteInfo();
        $file_path = Util::joinNotEmpty(DIRECTORY_SEPARATOR, [$this->template_dir, $routeInfo[1], $file_name]);
        $this->sendFile($file_path);
    }

}