<?php

namespace Tiny\Plugin\develop\controller;


use Tiny\Abstracts\AbstractBootstrap;
use Tiny\Application;
use Tiny\Plugin\develop\DevelopController;

class index extends DevelopController
{

    public function beforeAction(array $params)
    {
        $params = parent::beforeAction($params);

        if ($this->authDevelopKey()) {  //认证 通过
            $url = Application::url($this->getRequest(), ['', 'syslog', 'index']);
            Application::redirect($this->getResponse(), $url);
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

        self::_setDevelopKey($this->getRequest(), $develop_key);
        if (self::authDevelopKey()) {  //认证 通过
            Application::app()->redirect($this->getResponse(), Application::url($this->getRequest(), ['', 'syslog', 'index']));
        } else {
            $this->_showLoginBox($develop_key);
        }
    }

}