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
        if (!self::authDevelopKey()) {  //认证 通过
            Application::forward($this->getRequest(), $this->getResponse(), ['', '', 'auth']);
        }
        $this->display();
    }

    public function auth()
    {
        $develop_key = $this->_post('develop_key', '');

        self::_setDevelopKey($this->getRequest(), $develop_key);
        if (self::authDevelopKey()) {  //认证 通过
            Application::redirect($this->getResponse(), Application::url($this->getRequest(), ['', '', 'index']));
        } else {
            $this->_showLoginBox($develop_key);
        }
    }

}