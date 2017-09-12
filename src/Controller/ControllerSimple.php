<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/25 0025
 * Time: 14:52
 */

namespace Tiny\Controller;


use Tiny\Abstracts\AbstractController;
use Tiny\Func;
use Tiny\Request;
use Tiny\Response;
use Tiny\View\ViewSimple;

class ControllerSimple extends AbstractController
{
    private $_view_dir = '';
    private $_widget_dir = '';

    final public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->setView(new ViewSimple());

        ViewSimple::preTreatmentDisplay(function ($file_path, $params) {
            $params = self::extendAssign($this->getRequest(), $params);
            static::fire('preDisplay', [$this, $file_path, $params]);
            return $params;
        });

        ViewSimple::preTreatmentWidget(function ($file_path, $params) {
            $params = self::extendAssign($this->getRequest(), $params);
            static::fire('preWidget', [$this, $file_path, $params]);
            return $params;
        });
    }

    public function setTemplatePath($view_dir, $widget_dir)
    {
        $this->_view_dir = $view_dir;
        $this->_widget_dir = $widget_dir;
    }


    /**
     * @param string $tpl_path
     */
    protected function display($tpl_path = '')
    {
        $routeInfo = $this->getRequest()->getRouteInfo();
        $tpl_path = Func::trimlower($tpl_path);
        if (empty($tpl_path)) {
            $tpl_path = $routeInfo[2] . '.php';
        } else {
            $tpl_path = Func::stri_endwith($tpl_path, '.php') ? $tpl_path : "{$tpl_path}.php";
        }
        $file_path = Func::joinNotEmpty(DIRECTORY_SEPARATOR, [$this->_view_dir, $routeInfo[0], $routeInfo[1], $tpl_path]);

        $view = $this->getView();
        $params = $view->getAssign();

        $layout = $this->getLayout();
        if (!empty($layout)) {
            $layout_tpl = Func::stri_endwith($layout, '.php') ? $layout : "{$layout}.php";
            $layout_path = Func::joinNotEmpty(DIRECTORY_SEPARATOR, [$this->_view_dir, $routeInfo[0], $layout_tpl]);
            if (is_file($layout_path)) {
                ob_start();
                ob_implicit_flush(false);
                $view->display($file_path, $params);
                $action_content = ob_get_clean();
                $params['action_content'] = $action_content;
                $view->display($layout_path, $params);
                return;
            }
        }

        $view->display($file_path, $params);
    }

    /**
     * @param string $tpl_path
     * @param array $params
     * @return string
     * @throws \Tiny\Exception\AppStartUpError
     */
    protected function widget($tpl_path, array $params = [])
    {
        $tpl_path = Func::trimlower($tpl_path);
        if (empty($tpl_path)) {
            return '';
        }
        $tpl_path = Func::stri_endwith($tpl_path, '.php') ? $tpl_path : "{$tpl_path}.php";
        $file_path = Func::joinNotEmpty(DIRECTORY_SEPARATOR, [$this->_widget_dir, $tpl_path]);
        $buffer = $this->getView()->widget($file_path, $params);
        return $buffer;
    }

}