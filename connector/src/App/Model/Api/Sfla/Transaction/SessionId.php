<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Sfla\Transaction;

/**
 * Class SessionId described Sfla Api transaction: SessionId
 * https://developer.salesforce.com/docs/atlas.en-us.live_agent_rest.meta/live_agent_rest/live_agent_rest_SessionId.htm
 * @package App\Model\Api\Sfla\Transaction
 */
class SessionId extends \App\Model\Api\Sfla\Transaction
{
    /**
     * Method GET
     */
    const METHOD = \App\Model\Request\I\Base::GET;

    /**
     * url path
     */
    const CONVERSATION_URL = '/chat/rest/System/SessionId';

    /**
     * Required headers
     * @var array
     */
    protected $requiredHeaders = [
        'X-LIVEAGENT-API-VERSION',
        'X-LIVEAGENT-AFFINITY',
    ];
}