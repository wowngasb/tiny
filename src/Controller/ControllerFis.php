<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/25 0025
 * Time: 14:52
 */

namespace Tiny\Controller;

use Tiny\Abstracts\AbstractController;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;
use Tiny\Util;
use Tiny\View\ViewFis;

abstract class ControllerFis extends AbstractController
{
    final public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        parent::__construct($request, $response);
        $this->setView(new ViewFis());

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

    public function setFisReleasePath($config_dir, $template_dir)
    {
        ViewFis::setFis($config_dir, $template_dir);
    }

    /**
     * @param string $tpl_path
     */
    public function display($tpl_path = '')
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
        $params = $view->getAssign();
        $response = $this->getResponse();
        $layout = $this->getLayout();
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