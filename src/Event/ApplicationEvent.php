<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/23 0023
 * Time: 10:37
 */

namespace Tiny\Event;

use Tiny\Abstracts\AbstractEvent;
use Tiny\Application;
use Tiny\Interfaces\RequestInterface;
use Tiny\Interfaces\ResponseInterface;
use Tiny\Util;

class ApplicationEvent extends AbstractEvent
{

    /**
     * HttpEvent constructor.
     * @param string $type
     * @param Application $object
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct($type, Application $object, RequestInterface $request, ResponseInterface $response)
    {
        parent::__construct($type, $object, [
            'request' => $request,
            'response' => $response,
        ]);
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return Util::v($this->_params, 'request', null);
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return Util::v($this->_params, 'response', null);
    }


    /**
     * @return Application
     */
    public function getObject()
    {
        return $this->_object;
    }
}