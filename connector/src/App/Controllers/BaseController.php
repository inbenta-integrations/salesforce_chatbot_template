<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Controllers;

use BadMethodCallException;
use App\Model\Exception as AppEx;

abstract class BaseController
{
    /**
     * @var \App\Facade\I\Request
     */
    private $request;

    /**
     * @var \App\Facade\I\Response
     */
    private $response;

    /**
     * BaseController constructor.
     * @param \App\Facade\I\Request $request
     * @param \App\Facade\I\Response $response
     */
    public function __construct(\App\Facade\I\Request $request, \App\Facade\I\Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Execute an action on the controller.
     *
     * @param  string $method
     * @param  array $parameters
     */
    public function callAction($method, $parameters)
    {
        call_user_func_array([$this, $method], $parameters);
    }

    /**
     * @return \App\Facade\I\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return \App\Facade\I\Response
     */
    protected function getResponse()
    {
        return $this->response;
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    public function __destruct()
    {
        // empty
    }

    protected function validateRequest(\App\Facade\I\Request $request)
    {
        return;
    }

    /**
     * @param $message
     * @return AppEx
     * @throws \Exception
     */
    protected function error($message)
    {
        $ex = new AppEx($message);
        $date = new \DateTime(null, new \DateTimeZone('UTC'));

        $data = [
            'log_id' => uniqid(),
            'datetime_utc' => $date->format('Y-m-d H:i:s'),
            'request'      => $this->getRequest(),
            'response'      => $this->getResponse()
        ];

        $ex->data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response = [
            'log_id' => $data['log_id'],
            'message' => $ex->getMessage()
        ];

        $ex->response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $ex;
    }
}