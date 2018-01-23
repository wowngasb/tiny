<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/1 0001
 * Time: 17:39
 */

namespace Tiny\View;


use Tiny\Interfaces\ResponseInterface;
use Tiny\Interfaces\ViewInterface;
use Tiny\Plugin\Fis;

class FisView extends SimpleView implements ViewInterface
{

    public static function setFis($config_dir, $template_dir)
    {
        Fis::initFisResource($config_dir, $template_dir);
    }

    /**
     * 渲染一个 widget 视图模板, 得到结果
     * @param ResponseInterface $response
     * @param string $widget_path 视图模板的文件, 绝对路径, 一般这个路径由Controller提供
     * @param array $tpl_vars 关联数组, 模板变量
     * @return string
     */
    public function widget(ResponseInterface $response, $widget_path, array $tpl_vars = [])
    {
        $_pre_widget = $this->getPreWidget();
        $tpl_vars = !empty($_pre_widget) ? call_user_func_array($_pre_widget, [$widget_path, $tpl_vars]) : $tpl_vars;
        return Fis::widget($response, $widget_path, $tpl_vars);
    }

    /**
     * 渲染一个视图模板, 并直接输出给请求端
     * @param ResponseInterface $response
     * @param string $view_path 视图模板的文件, 绝对路径, 一般这个路径由Controller提供
     * @param array $tpl_vars 关联数组, 模板变量
     * @return string
     */
    public function display(ResponseInterface $response, $view_path, array $tpl_vars = [])
    {
        $_pre_display = $this->getPreDisplay();
        $tpl_vars = $_pre_display ? call_user_func_array($_pre_display, [$view_path, $tpl_vars]) : $tpl_vars;
        return Fis::display($response, $view_path, $tpl_vars);
    }

}