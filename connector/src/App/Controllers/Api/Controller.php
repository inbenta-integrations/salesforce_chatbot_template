<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Model\Config;
use App\Model\Session;
use App\Model\Varien\DataObject;

abstract class Controller extends BaseController
{
    /**
     * Execute an action on the controller.
     *
     * @param  string $method
     * @param  array $parameters
     * @throws \Exception
     */
    public function callAction($method, $parameters)
    {
        try {
            $this->validateRequest($this->getRequest());
            Session::startSession();
            // Set session, but if not exists - create it
            $sessionKey = $this->getRequest()->getHeader('X-Adapter-Session-Id') ?? hash('sha256', $this->getRequest()->getHeader('X-Inbenta-Session'));
            Session::setSessionId($sessionKey);
            call_user_func_array([$this, $method], $parameters);

        } catch (\App\Model\Api\Inbenta\Exception $authException) {
            $this->getResponse()
                ->setSuccess(false)
                ->setData('code', 401)
                ->setBody(['message' => $authException->getMessage()]);

        } catch (\App\Model\Exception $ex) {
            $this->getResponse()
                ->setSuccess(false)
                ->setData('code', 200)
                ->setBody(['message' => $ex->getMessage()]);

        } catch (\Exception $ex) {
            $this->getResponse()
                ->setSuccess(false)
                ->setData('code', 403)
                ->setBody(['message' => $ex->getMessage()]);

        } finally {
            $this->setContentType();
            $response = $this->getResponse();
            if ($code = $response->getData('code'))
                http_response_code($code);
            echo $response->getJsonFormatted();
            Session::destroy();
        }
    }

    protected function setContentType()
    {
        header("Content-type: application/json; charset=utf-8");
    }
}