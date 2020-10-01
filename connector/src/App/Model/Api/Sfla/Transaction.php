<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Sfla;

/**
 * Class Transaction abstract class for Sfla transaction pool
 * @package App\Model\Api\Sfla
 */
abstract class Transaction extends \App\Model\Api\Transaction
{
    /**
     * Set default headers for sfla api
     * @var array
     */
    protected $headers = [
        'X-LIVEAGENT-API-VERSION' => 47
    ];
}