<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/7
 * Time: 13:55
 */

namespace Tiny\Plugin\graphiql\controller;


use Tiny\Application;
use Tiny\Plugin\graphiql\base\BaseGraphiQLController;
use Tiny\Request;

class index extends BaseGraphiQLController
{

    public function index()
    {
        if (!self::authDevelopKey()) {  //认证 通过
            Application::app()->forward($this->getRequest(), $this->getResponse(), ['', '', 'auth']);
        }
        $this->display();
    }

    public function auth()
    {
        $develop_key = $this->_post('develop_key', '');

        $this->_setCookieDevelopKey($develop_key);
        if (self::authDevelopKey()) {  //认证 通过
            Application::redirect(Request::urlTo($this->getRequest(), ['', '', 'index']));
        } else {
            $this->_showLoginBox($develop_key);
        }
    }

}