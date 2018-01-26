<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/25 0025
 * Time: 15:11
 */

namespace Tiny\Controller;


use Tiny\Abstracts\AbstractController;
use Tiny\Event\ControllerEvent;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;
use Tiny\View\BladeView;

class BladeController extends AbstractController
{

    final public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        parent::__construct($request, $response);
        $this->setView(new BladeView());

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

    /**
     * @param string $views_dir
     * @param string $cache_dir
     */
    public static function setBladePath($views_dir, $cache_dir)
    {
        BladeView::setBlade($views_dir, $cache_dir);
    }

    public function widget($tpl_path, array $params = [])
    {
        $tpl_path = trim($tpl_path);

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
    protected function display($tpl_path = '', array $params = [])
    {
        $tpl_path = trim($tpl_path);
        $routeInfo = $this->getRequest()->getRouteInfo();
        if (empty($tpl_path)) {
            $file_path = strtolower("{$routeInfo[0]}.{$routeInfo[1]}.{$routeInfo[2]}");
        } else {
            $file_path = $tpl_path;
        }

        $view = $this->getView();
        $params = array_merge($view->getAssign(), $params);
        static::fire(new ControllerEvent('preDisplay', $this, $file_path, $params));

        $response = $this->getResponse();
        $html = $view->display($response, $tpl_path, $params);

        $this->getResponse()->appendBody($html);
    }

}