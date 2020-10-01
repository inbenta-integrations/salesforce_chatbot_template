<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Request\Response;

class Curl implements \App\Model\Request\I\Response
{
    /**
     * @var int
     */
    private $httpCode;

    /**
     * @var \App\Model\Varien\DataObject
     */
    private $headers;

    /**
     * @var mixed
     */
    private $body;

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return $this->httpCode;
    }

    /**
     * @return \App\Model\Varien\DataObject
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->httpCode = $code;

        return $this;
    }

    /**
     * @param \App\Model\Varien\DataObject $headers
     *
     * @return $this
     */
    public function setHeaders(\App\Model\Varien\DataObject $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param string $key
     * @return \App\Model\Varien\DataObject
     */
    public function getHeader($key)
    {
        return $this->headers->getData($key);
    }

    /**
     * @param string $body
     *
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }
}