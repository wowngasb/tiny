<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/25 0025
 * Time: 13:42
 */

namespace Tiny\Interfaces;


interface ViewInterface
{

    /**
     * @return callable | null
     */
    public function getPreDisplay();

    /**
     * 用于添加 display 前的预处理函数  主要用于 添加通用变量 触发事件
     * @param callable $pre_display 参数为 pre_display($view_path, array $tpl_vars = [])
     */
    public function setPreDisplay($pre_display);

    /**
     * @return callable | null
     */
    public function getPreWidget();

    /**
     * 用于添加 widget 前的预处理函数  主要用于 添加通用变量 触发事件
     * @param callable $pre_widget 参数为  pre_widget($widget_path, array $tpl_vars = [])
     */
    public function setPreWidget(callable $pre_widget);

    /**
     * 渲染一个组件模板, 得到结果
     * @param ResponseInterface $response
     * @param string $widget_path 视图模板的文件, 绝对路径, 一般这个路径由Controller提供
     * @param array $tpl_vars 关联数组, 模板变量
     * @return string
     */
    public function widget(ResponseInterface $response, $widget_path, array $tpl_vars = []);

    /**
     * 渲染一个视图模板, 得到结果
     * @param ResponseInterface $response
     * @param string $view_path 视图模板的文件, 绝对路径, 一般这个路径由Controller提供
     * @param array $tpl_vars 关联数组, 模板变量
     * @return string
     */
    public function display(ResponseInterface $response, $view_path, array $tpl_vars = []);

    /**
     * 添加 模板变量
     * @param mixed $name 字符串或者关联数组, 如果为字符串, 则$value不能为空, 此字符串代表要分配的变量名. 如果为数组, 则$value须为空, 此参数为变量名和值的关联数组.
     * @param mixed $value 分配的模板变量值
     * @return ViewInterface
     */
    public function assign($name, $value = null);

    /**
     * 获取所有 模板变量
     * @return array
     */
    public function getAssign();
}