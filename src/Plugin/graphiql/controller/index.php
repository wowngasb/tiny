<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/7
 * Time: 13:55
 */

namespace Tiny\Plugin\graphiql\controller;


use Tiny\Application;
use Tiny\Plugin\graphiql\GraphiQLController;

class index extends GraphiQLController
{

    public function index()
    {
        if (!self::authDevelopKey($this->getRequest())) {  //认证 不通过
            Application::forward($this->getRequest(), $this->getResponse(), ['', '', 'auth']);
        }
        $this->display();
    }

    public function auth($back = '')
    {
        $develop_key = $this->_post('develop_key', '');

        self::_setDevelopKey($this->getRequest(), $develop_key);
        if (self::authDevelopKey($this->getRequest())) {  //认证 通过
            $url = Application::url($this->getRequest(), ['', '', 'index']);
            return !empty($back) ? Application::redirect($this->getResponse(), $back) : Application::redirect($this->getResponse(), $url);
        } else {
            return $this->_showLoginBox($develop_key);
        }
    }

}