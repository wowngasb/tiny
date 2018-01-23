<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/24 0024
 * Time: 14:59
 */

namespace Tiny\View;

use Tiny\Interfaces\ResponseInterface;
use Tiny\Interfaces\ViewInterface;


/**
 * Class View
 * @package Tiny
 */
class SimpleView implements ViewInterface
{

    private $_tpl_vars = [];
    private $_pre_display = null;
    private $_pre_widget = null;

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
        return static::display($response, $widget_path, $tpl_vars);
    }

    /**
     * 渲染一个 widget 视图模板, 得到结果
     * @param ResponseInterface $response
     * @param string $view_path 视图模板的文件, 绝对路径, 一般这个路径由Controller提供
     * @param array $tpl_vars 关联数组, 模板变量
     * @return string
     */
    public function display(ResponseInterface $response, $view_path, array $tpl_vars = [])
    {
        $_pre_display = $this->getPreDisplay();
        $tpl_vars = $_pre_display ? call_user_func_array($_pre_display, [$view_path, $tpl_vars]) : $tpl_vars;
        return $response->requireForRender($view_path, $tpl_vars);
    }

    /**
     * 添加 模板变量
     * @param mixed $name 字符串或者关联数组, 如果为字符串, 则$value不能为空, 此字符串代表要分配的变量名. 如果为数组, 则$value须为空, 此参数为变量名和值的关联数组.
     * @param mixed $value 分配的模板变量值
     * @return ViewInterface
     */
    public function assign($name, $value = null)
    {
        if (is_array($name)) {
            $this->_tpl_vars = array_merge($this->getAssign(), $name);
            return $this;
        }
        $this->_tpl_vars[$name] = $value;
        return $this;
    }

    /**
     * 获取所有 模板变量
     * @return array
     */
    public function getAssign()
    {
        return $this->_tpl_vars;
    }

    /**
     * @return callable | null
     */
    public function getPreDisplay()
    {
        return $this->_pre_display;
    }

    /**
     * 用于添加 display 前的预处理函数  主要用于 添加通用变量 触发事件
     * @param callable $pre_display 参数为 pre_display($view_path, array $tpl_vars = [])
     */
    public function setPreDisplay($pre_display)
    {
        $this->_pre_display = $pre_display;
    }

    /**
     * @return callable | null
     */
    public function getPreWidget()
    {
        return $this->_pre_widget;
    }

    /**
     * 用于添加 widget 前的预处理函数  主要用于 添加通用变量 触发事件
     * @param callable $pre_widget 参数为  pre_widget($widget_path, array $tpl_vars = [])
     */
    public function setPreWidget(callable $pre_widget)
    {
        $this->_pre_widget = $pre_widget;
    }
}