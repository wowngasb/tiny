<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/7
 * Time: 14:02
 */

namespace Tiny\Plugin\develop\controller;


use Tiny\Plugin\develop\DevelopController;
use Tiny\Util;

class assets extends DevelopController
{

    public function index()
    {
        $file_name = $this->getRequest()->getRequestUri();
        $file_name = explode('#', $file_name)[0];
        $file_name = explode('?', $file_name)[0];
        $routeInfo = $this->getRequest()->getRouteInfo();
        $file_name = str_replace("/{$routeInfo[0]}/{$routeInfo[1]}/", '', $file_name);
        $file_name = str_replace(['/', "\\"], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $file_name);
        $file_path = Util::joinNotEmpty(DIRECTORY_SEPARATOR, [$this->template_dir, $routeInfo[1], $file_name]);
        $this->sendFile($file_path);
    }

}