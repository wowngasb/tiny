<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/25 0025
 * Time: 14:52
 */

namespace Tiny\Controller;


use Tiny\Abstracts\AbstractController;
use Tiny\Event\ControllerEvent;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;
use Tiny\Util;
use Tiny\View\SimpleView;

abstract class SimpleController extends AbstractController
{

    private $_css_tpl = '<!--[TINY_CSS_LINKS_HOOK]-->';
    private $_js_tpl = '<!--[TINY_CSS_LINKS_HOOK]-->';
    private $_script_src_dict = [];
    private $_script_src_map = [];
    private $_link_href_map = [];
    private $_css_text_dict = [];
    private $_script_text_dict = [];

    private $_view_dir = '';
    private $_widget_dir = '';

    final public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        parent::__construct($request, $response);
        $this->setView(new SimpleView());

        $this->getView()->setPreDisplay(function ($file_path, $params) {
            false && func_get_args();

            $params = $this->extendAssign($params);
            return $params;
        });

        $this->getView()->setPreWidget(function ($file_path, $params) {
            false && func_get_args();

            $params = $this->extendAssign($params);
            return $params;
        });
    }

    public function setTemplatePath($view_dir, $widget_dir)
    {
        $this->_view_dir = $view_dir;
        $this->_widget_dir = $widget_dir;
    }

    public function widget($tpl_path, array $params = [])
    {
        $tpl_path = Util::stri_endwith($tpl_path, '.php') ? $tpl_path : "{$tpl_path}.php";
        $tpl_path = Util::trimlower($tpl_path);

        $file_path = Util::joinNotEmpty(DIRECTORY_SEPARATOR, [$this->_widget_dir, $tpl_path]);

        $view = $this->getView();
        $params = array_merge($view->getAssign(), $params);
        static::fire(new ControllerEvent('preWidget', $this, $file_path, $params));

        $html = $view->widget($this->getResponse(), $file_path, $params);
        return $html;
    }

    /**
     * @param string $tpl_path
     * @param array $params
     */
    protected function display($tpl_path = '', array $params = [])
    {
        $routeInfo = $this->getRequest()->getRouteInfo();
        $tpl_path = trim($tpl_path);
        if (empty($tpl_path)) {
            $tpl_path = $routeInfo[2] . '.php';
        } else {
            $tpl_path = Util::stri_endwith($tpl_path, '.php') ? $tpl_path : "{$tpl_path}.php";
        }
        $tpl_path = Util::trimlower($tpl_path);

        $file_path = Util::joinNotEmpty(DIRECTORY_SEPARATOR, [$this->_view_dir, Util::trimlower($routeInfo[1]), $tpl_path]);

        $view = $this->getView();
        $params = array_merge($view->getAssign(), $params);
        static::fire(new ControllerEvent('preDisplay', $this, $file_path, $params));

        $response = $this->getResponse();
        $layout = $this->_getLayout();
        if (!empty($layout)) {
            $layout_tpl = Util::stri_endwith($layout, '.php') ? $layout : "{$layout}.php";
            $layout_path = Util::joinNotEmpty(DIRECTORY_SEPARATOR, [$this->_view_dir, $layout_tpl]);
            $action_content = $view->display($response, $file_path, $params);
            $params['action_content'] = $action_content;
            $html = $view->display($response, $layout_path, $params);
        } else {
            $html = $view->display($response, $file_path, $params);
        }

        $render_html = $this->_renderResponse($html);
        $this->getResponse()->appendBody($render_html);
    }

    public function script($src, $priority = 10, $type = 'text/javascript')
    {
        if (empty($src)) {
            return '';
        }
        $src = Util::stri_endwith($src, '.js') ? $src : "{$src}.js";
        if (isset($this->_script_src_map[$src])) {
            return '';
        } else {
            $assets_ver = '1.0';
            $js_ver = !empty($assets_ver) ? "?v={$assets_ver}" : '';
            $this->_script_src_map[$src] = 1;
            $this->_addScriptSrc("<script src=\"{$src}{$js_ver}\" type=\"{$type}\"></script>", $priority);
            return '';
        }
    }

    public function link($href, $rel = 'stylesheet')
    {
        if (empty($href)) {
            return '';
        }
        $href = Util::stri_endwith($href, '.css') ? $href : "{$href}.css";
        if (isset($this->_link_href_map[$href])) {
            return '';
        } else {
            $assets_ver = '1.0';
            $css_ver = !empty($assets_ver) ? "?v={$assets_ver}" : '';
            $this->_link_href_map[$href] = "<link href=\"{$href}{$css_ver}\" rel=\"{$rel}\">";
            return '';
        }
    }

    private function _addScriptSrc($str, $priority)
    {
        $priority = $priority > 0 ? intval($priority) : 0;
        if (!isset($this->_script_src_dict[$priority])) {
            $this->_script_src_dict[$priority] = [];
        }
        $this->_script_src_dict[$priority][] = $str;
    }

    private function _addScriptText($str, $priority)
    {
        $priority = $priority > 0 ? intval($priority) : 0;
        if (!isset($this->_script_text_dict[$priority])) {
            $this->_script_text_dict[$priority] = [];
        }
        $this->_script_text_dict[$priority][] = $str;
    }

    //输出js，将页面的js源代码集合到pool，一起输出
    private function _renderScriptPool()
    {
        $html = '';
        if (!empty($this->_script_src_dict)) {
            $priorities = array_keys($this->_script_src_dict);
            asort($priorities);
            foreach ($priorities as $priority) {
                $html .= "\n" . implode("\n", $this->_script_src_dict[$priority]);
            }
        }

        $script_s = '<script type="text/javascript">';
        $script_e = '</script>';
        $tmp_js = '';
        if (!empty($this->_script_text_dict)) {
            $priorities = array_keys($this->_script_text_dict);
            asort($priorities);
            foreach ($priorities as $priority) {
                $tmp_js .= "\n" . implode("\n", $this->_script_text_dict[$priority]);
            }
        }
        if (!empty($tmp_js)) {
            $html .= <<<TAG
{$script_s}
{$tmp_js}
{$script_e}
TAG;
        }

        return $html;
    }

    //输出css，将页面的css源代码集合到pool，一起输出
    private function _renderCssPool()
    {
        $html = '';
        if (!empty($this->_link_href_map)) {
            $html .= "\n" . implode("\n", $this->_link_href_map) . "\n";
        }

        $style_s = '<style type="text/css">';
        $style_e = '</style>';
        $tmp_css = '';
        if (!empty($this->_css_text_dict)) {
            $tmp_css = implode("\n", $this->_css_text_dict);
        }

        if (!empty($tmp_css)) {
            $html .= <<<TAG
{$style_s}
{$tmp_css}
{$style_e}
TAG;
        }

        return $html;
    }

    public function scriptStart()
    {
        $this->getResponse()->ob_start();
        return '';
    }

    public function scriptEnd($priority = 20)
    {
        $script = $this->getResponse()->ob_get_clean();
        $text = self::_getScriptContent($script);
        if (!empty($text)) {
            $this->_addScriptText($text, $priority);
        }
        return '';
    }

    private static function _getScriptContent($script)
    {
        $script = trim($script);
        $s_idx = stripos($script, '<script');
        $e_idx = stripos($script, '>', $s_idx + 1);
        if ($e_idx === false || $s_idx === false) {
            return $script;
        }
        $end_idx = strripos($script, '</script>', $e_idx);
        $text = substr($script, $e_idx + 1, $end_idx - ($e_idx + 1));
        return $text;
    }

    private static function _getStyleContent($style)
    {
        $style = trim($style);
        $s_idx = stripos($style, '<style');
        $e_idx = stripos($style, '>', $s_idx + 1);
        if ($e_idx === false || $s_idx === false) {
            return $style;
        }
        $end_idx = strripos($style, '</style>', $e_idx);
        $text = substr($style, $e_idx + 1, $end_idx - ($e_idx + 1));
        return $text;
    }

    public function styleStart()
    {
        $this->getResponse()->ob_start();
        return '';
    }

    public function styleEnd()
    {
        $style = $this->getResponse()->ob_get_clean();
        $text = self::_getStyleContent($style);
        if (!empty($text)) {
            $this->_css_text_dict[] = $text;
        }
        return '';
    }

    public function placeHolder($mode)
    {
        return $mode == 'js' ? $this->_js_tpl : ($mode == 'css' ? $this->_css_tpl : '');
    }

    private function _renderResponse($strContent)
    {
        $cssIntPos = strpos($strContent, $this->_css_tpl);
        $css_content = trim($this->_renderCssPool());
        if (!empty($css_content)) {
            if ($cssIntPos !== false) {
                $strContent = substr_replace($strContent, $css_content, $cssIntPos, strlen($this->_css_tpl));
            }
        }

        $js_content = trim($this->_renderScriptPool());
        if (!empty($js_content)) {
            $jsIntPos = strpos($strContent, $this->_js_tpl);
            if ($jsIntPos !== false) {
                $strContent = substr_replace($strContent, $js_content, $jsIntPos, strlen($this->_js_tpl));
            }
        }
        return $strContent;
    }

}