<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/7
 * Time: 13:57
 */

namespace Tiny\Plugin\graphiql;


use Tiny\Util;
use Tiny\Plugin\DevAuthController;

class GraphiQLController extends DevAuthController
{

    public function beforeAction(array $params)
    {
        $params = parent::beforeAction($params);

        $template_dir = Util::joinNotEmpty(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'view']);
        $widget_dir = Util::joinNotEmpty(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'widget']);
        $this->setTemplatePath($template_dir, $widget_dir);
        $this->template_dir = $template_dir;
        $this->assign('tool_title', 'GraphiQL 开发者工具');
        $this->_checkRequestDevelopKeyToken();

        return $params;
    }
}