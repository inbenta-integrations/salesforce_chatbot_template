<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Inbenta\Transaction;

class Session extends \App\Model\Api\Transaction
{
    /**
     * Request method
     */
    const METHOD = \App\Model\Request\I\Base::GET;

    /**
     * Request path
     */
    const CONVERSATION_URL = '/v1/conversation/history';

    /**
     * Required headers for the transaction
     * @var array
     */
    protected $requiredHeaders = [
        'Authorization',
        'X-Inbenta-Key',
        'X-Inbenta-Session',
    ];

    /**
     * @param string $url
     * @return bool
     * @throws \Exception
     */
    public function process(string $url)
    {
        parent::process($url);

        if ($this->getResponse()->getCode() !== 200)
            return false;

        return true;
    }
}