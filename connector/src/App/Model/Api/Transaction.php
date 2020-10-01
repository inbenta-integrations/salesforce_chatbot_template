<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api;

use App\Model\Config;
use App\Model\Session;

/**
 * Class Transaction Main class for all Api requests
 * @package App\Model\Api
 */
abstract class Transaction
{
    /**
     * Reqiest method, POST, GET etc.
     */
    const METHOD = '';

    /**
     * Request path
     */
    const CONVERSATION_URL = '';

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Request parameters
     * @var array
     */
    protected $request = [];

    /**
     * @var array
     */
    protected $requiredHeaders = [];

    /**
     * @var \App\Model\Request\I\Base
     */
    protected $requester;

    /**
     * Transaction constructor.
     * @param \App\Model\Request\I\Base $requester
     */
    public function __construct(\App\Model\Request\I\Base $requester)
    {
        $this->requester = $requester;
    }

    /**
     * @param $key
     * @param $value
     */
    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * Add array of headers
     * @param array $headers
     */
    public function addHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Clean previous set Headers and set new
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * add Request parameters
     * @param mixed $request
     * @param mixed $value
     */
    public function addRequest($request, $value = '')
    {
        if (is_array($request)) {
            $this->request = array_merge($this->request, $request);
        } else {
            $this->request[$request] = $value;
        }
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * Short alias to response object
     * @return \App\Model\Request\I\Response
     */
    public function getResponse()
    {
        return $this->requester->response();
    }

    /**
     * @param string $url
     * @return \App\Model\Varien\DataObject
     * @throws \Exception
     */
    public function process(string $url)
    {
        $url .= static::CONVERSATION_URL;
        $this->checkRequiredHeaders();
        // $this->logInfo([
        //     'state'          => 'beforeRequest',
        //     'tranId'         => Session::getSessionId(),
        //     'url'            => $url,
        //     'method'         => static::METHOD,
        //     'requestHeaders' => $this->getHeaders(),
        //     'request'        => $this->request,
        // ]);

        $this->requester->send($url, $this->request, ['method' => static::METHOD], $this->getHeaders());
        // $this->logInfo([
        //     'state'           => 'afterRequest',
        //     'tranId'          => Session::getSessionId(),
        //     'url'             => $url,
        //     'method'          => static::METHOD,
        //     'responseCode'    => $this->getResponse()->getCode(),
        //     'responseHeaders' => $this->getResponse()->getHeaders()->getData(),
        //     'responseBody'    => $this->getResponse()->getBody(),
        // ]);

        try {
            $responseBody = json_decode($this->getResponse()->getBody(), true);
            if (!$responseBody)
                $responseBody = ['code' => $this->getResponse()->getCode(), 'body' => $this->getResponse()->getBody()];

        } catch (\Exception $ex) {
            throw $ex;
        }

        return new \App\Model\Varien\DataObject($responseBody);
    }

    /**
     * Check required headers
     * @throws \Exception
     */
    protected function checkRequiredHeaders()
    {
        foreach ($this->requiredHeaders as $header) {
            if ($header !== 'Origin' && !isset($this->headers[$header])) {
                throw new \Exception("Check headers - $header is required!");
            }
        }
    }
}