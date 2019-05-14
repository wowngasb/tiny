<?php

namespace Tiny\Plugin\develop;

use Tiny\Plugin\DevAuthController;

class DevelopController extends DevAuthController
{

    public function beforeAction(array $params)
    {
        $params = parent::beforeAction($params);

        $template_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'view';
        $widget_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'widget';
        $this->setTemplatePath($template_dir, $widget_dir);
        $this->template_dir = $template_dir;
        $this->assign('tool_title', 'Tiny 开发者工具');
        $this->_checkRequestDevelopKeyToken();

        return $params;
    }


}