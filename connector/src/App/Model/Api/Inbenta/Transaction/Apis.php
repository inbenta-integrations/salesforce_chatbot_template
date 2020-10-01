<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Inbenta\Transaction;

use App\Model\Exception as AppEx;

class Apis extends \App\Model\Api\Transaction
{
    /**
     * Request method
     */
    const METHOD = \App\Model\Request\I\Base::GET;

    /**
     * Request path
     */
    const CONVERSATION_URL = '/v1/apis';

    /**
     * Required headers for the transaction
     * @var array
     */
    protected $requiredHeaders = [
        'Authorization',
        'X-Inbenta-Key',
        'Origin',
    ];

    /**
     * @param string $url
     * @return \App\Model\Varien\DataObject
     * @throws \Exception
     */
    public function process(string $url)
    {
        $response = parent::process($url);

        if ($this->getResponse()->getCode() !== 200)
            throw new AppEx(AppEx::E_APIS_RESPONSE);

        return $response;
    }
}