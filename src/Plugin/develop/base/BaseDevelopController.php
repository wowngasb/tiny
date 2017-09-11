<?php

namespace Tiny\Plugin\develop\base;

use Tiny\Plugin\DevAuthControllerSimple;

class BaseDevelopController extends DevAuthControllerSimple
{
    protected static $template_dir = '';

    public function beforeAction()
    {
        parent::beforeAction();
        $template_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        $this->setTemplatePath($template_dir, $template_dir);
        static::$template_dir = $template_dir;
        $this->assign('tool_title', 'Tiny 开发者工具');
        $this->_checkRequestDevelopKeyToken();
    }


}