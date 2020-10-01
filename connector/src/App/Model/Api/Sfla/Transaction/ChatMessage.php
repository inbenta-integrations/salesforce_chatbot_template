<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Sfla\Transaction;

/**
 * Class ChatMessage described Sfla Api transaction: ChatMessage
 * https://developer.salesforce.com/docs/atlas.en-us.live_agent_rest.meta/live_agent_rest/live_agent_rest_ChatMessage_request.htm
 * @package App\Model\Api\Sfla\Transaction
 */
class ChatMessage extends \App\Model\Api\Sfla\Transaction
{
    /**
     * Request method
     */
    const METHOD = \App\Model\Request\I\Base::POST;

    /**
     * Request path
     */
    const CONVERSATION_URL = '/chat/rest/Chasitor/ChatMessage';

    /**
     * Required headers for the transaction
     * @var array
     */
    protected $requiredHeaders = [
        'X-LIVEAGENT-API-VERSION',
        'X-LIVEAGENT-AFFINITY',
        'X-LIVEAGENT-SESSION-KEY',
//        'X-LIVEAGENT-SEQUENCE'
    ];
}