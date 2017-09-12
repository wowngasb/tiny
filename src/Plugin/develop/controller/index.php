<?php

namespace Tiny\Plugin\develop\controller;


use Tiny\Application;
use Tiny\Plugin\develop\base\BaseDevelopController;
use Tiny\Request;

class index extends BaseDevelopController
{

    public function beforeAction(array $params)
    {
        $params = parent::beforeAction($params);

        if ($this->authDevelopKey()) {  //认证 通过
            Application::redirect(Request::urlTo($this->getRequest(), ['', 'syslog', 'index']));
        }
        return $params;
    }

    public function index()
    {
        Application::forward($this->getRequest(), $this->getResponse(), ['', '', 'auth']);
    }

    public function auth()
    {
        $develop_key = $this->_post('develop_key', '');

        $this->_setCookieDevelopKey($develop_key);
        if (self::authDevelopKey()) {  //认证 通过
            Application::redirect(Request::urlTo($this->getRequest(), ['', 'syslog', 'index']));
        } else {
            $this->_showLoginBox($develop_key);
        }
    }

}