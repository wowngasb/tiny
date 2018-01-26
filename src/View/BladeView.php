<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/25 0025
 * Time: 15:10
 */

namespace Tiny\View;


use Philo\Blade\Blade;
use Tiny\Interfaces\ResponseInterface;
use Tiny\Interfaces\ViewInterface;

class BladeView extends SimpleView implements ViewInterface
{
    /** @var Blade */
    private static $_blade = null;

    /**
     * @param string $views_dir
     * @param string $cache_dir
     */
    public static function setBlade($views_dir, $cache_dir)
    {
        if (is_null(self::$_blade)) {
            self::$_blade = new Blade($views_dir, $cache_dir);
        }
    }


    private static function _render($name, array $data = [])
    {
        /** @var \Illuminate\View\Factory $view */
        $view = self::$_blade->view();
        return $view->make($name, $data)->render();
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
        return self::_render($widget_path, $tpl_vars);
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
        return self::_render($view_path, $tpl_vars);
    }
}