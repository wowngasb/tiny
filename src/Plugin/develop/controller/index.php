<?php

namespace Tiny\Plugin\develop\controller;


use Tiny\Abstracts\AbstractBoot;
use Tiny\Application;
use Tiny\Plugin\develop\DevelopController;

class index extends DevelopController
{

    public function beforeAction(array $params)
    {
        $params = parent::beforeAction($params);

        if (self::authDevelopKey($this->getRequest())) {  //认证 通过
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
        if (self::authDevelopKey($this->getRequest())) {  // 认证 通过
            $back = !empty($back) ? $back : Application::url($this->getRequest(), ['', 'syslog', 'index']);
            Application::app()->redirect($this->getResponse(), $back);
        } else {
            $this->_showLoginBox($develop_key);
        }
    }

}