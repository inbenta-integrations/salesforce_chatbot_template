<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Request\I;

interface Response
{
    /**
     * @param string|int $code
     */
    public function setCode($code);

    /**
     * @param \App\Model\Varien\DataObject $headers
     */
    public function setHeaders(\App\Model\Varien\DataObject $headers);

    /**
     * @param string $body
     */
    public function setBody($body);

    /**
     * @return string|int
     */
    public function getCode();

    /**
     * @return \App\Model\Varien\DataObject
     */
    public function getHeaders();

    /**
     * @return string
     */
    public function getBody();
}
