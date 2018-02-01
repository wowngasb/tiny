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
use Tiny\View\FisView;

abstract class FisController extends AbstractController
{
    final public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        parent::__construct($request, $response);
        $this->setView(new FisView());

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

    public static function setFisPath($config_dir, $template_dir)
    {
        FisView::setFis($config_dir, $template_dir);
    }

    public function widget($tpl_path, array $params = [])
    {
        $tpl_path = trim($tpl_path);
        if (empty($tpl_path)) {
            return '';
        } else {
            $tpl_path = Util::stri_endwith($tpl_path, '.php') ? $tpl_path : "{$tpl_path}.php";
        }

        $view = $this->getView();
        $params = array_merge($view->getAssign(), $params);
        static::fire(new ControllerEvent('preWidget', $this, $tpl_path, $params));

        $response = $this->getResponse();
        $html = $view->widget($response, $tpl_path, $params);
        return $html;
    }

    /**
     * @param string $tpl_path
     * @param array $params
     */
    public function display($tpl_path = '', array $params = [])
    {
        $tpl_path = trim($tpl_path);
        $routeInfo = $this->getRequest()->getRouteInfo();
        if (empty($tpl_path)) {
            $tpl_path = $routeInfo[2] . '.php';
        } else {
            $tpl_path = Util::stri_endwith($tpl_path, '.php') ? $tpl_path : "{$tpl_path}.php";
        }
        $file_path = "view/{$routeInfo[0]}/{$routeInfo[1]}/{$tpl_path}";

        $view = $this->getView();
        $params = array_merge($view->getAssign(), $params);
        static::fire(new ControllerEvent('preDisplay', $this, $file_path, $params));

        $response = $this->getResponse();
        $layout = $this->_getLayout();
        $html = '';
        if (!empty($layout)) {
            $layout_tpl = Util::stri_endwith($layout, '.php') ? $layout : "{$layout}.php";
            $layout_path = $file_path = "view/{$routeInfo[0]}/{$routeInfo[1]}/{$layout_tpl}";
            if (is_file($layout_path)) {
                $action_content = $view->display($response, $file_path, $params);

                $params['action_content'] = $action_content;
                $html = $view->display($response, $layout_path, $params);
            }
        } else {
            $html = $view->display($response, $file_path, $params);
        }
        $this->getResponse()->appendBody($html);
    }

}