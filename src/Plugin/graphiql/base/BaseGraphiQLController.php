<?php
/**
 * Created by PhpStorm.
 * User: a
 * Date: 2017/9/7
 * Time: 13:57
 */

namespace Tiny\Plugin\graphiql\base;


use Tiny\Plugin\DevAuthControllerSimple;

class BaseGraphiQLController extends DevAuthControllerSimple
{

    protected static $template_dir = '';

    public function beforeAction(array $params)
    {
        $params = parent::beforeAction($params);

        $template_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        $this->setTemplatePath($template_dir, $template_dir);
        static::$template_dir = $template_dir;
        $this->assign('tool_title', 'GraphiQL 开发者工具');
        $this->_checkRequestDevelopKeyToken();

        return $params;
    }
}