<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/1 0001
 * Time: 17:39
 */

namespace Tiny\View;


use Tiny\Plugin\Fis;
use Tiny\Interfaces\ViewInterface;

class ViewFis extends ViewSimple implements ViewInterface
{

    private static $pre_display = null;
    private static $pre_widget = null;

    public static function setFis($config_dir, $template_dir)
    {
        Fis::initFisResource($config_dir, $template_dir);
    }

    /**
     * 渲染一个 widget 视图模板, 得到结果
     * @param string $widget_path 视图模板的文件, 绝对路径, 一般这个路径由Controller提供
     * @param array $tpl_vars 关联数组, 模板变量
     * @return string
     */
    public static function widget($widget_path, array $tpl_vars = [])
    {
        $tpl_vars = self::$pre_widget ? call_user_func_array(self::$pre_widget, [$widget_path, $tpl_vars]) : $tpl_vars;
        return Fis::widget($widget_path, $tpl_vars);
    }

    /**
     * 渲染一个视图模板, 并直接输出给请求端
     * @param string $view_path 视图模板的文件, 绝对路径, 一般这个路径由Controller提供
     * @param array $tpl_vars 关联数组, 模板变量
     * @return string
     */
    public static function display($view_path, array $tpl_vars = [])
    {
        $tpl_vars = self::$pre_display ? call_user_func_array(self::$pre_display, [$view_path, $tpl_vars]) : $tpl_vars;
        return Fis::display($view_path, $tpl_vars);
    }


    /**
     * 用于添加 display 前的预处理函数  主要用于 添加通用变量 触发事件
     * @param callable $pre_display 参数为 pre_display($view_path, array $tpl_vars = [])
     */
    public static function preTreatmentDisplay(callable $pre_display)
    {
        self::$pre_display = $pre_display;
    }

    /**
     * 用于添加 widget 前的预处理函数  主要用于 添加通用变量 触发事件
     * @param callable $pre_widget 参数为  pre_widget($widget_path, array $tpl_vars = [])
     */
    public static function preTreatmentWidget(callable $pre_widget)
    {
        self::$pre_widget = $pre_widget;
    }

}