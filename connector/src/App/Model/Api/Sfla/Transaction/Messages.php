<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Sfla\Transaction;

/**
 * Class Messages described Sfla Api transaction: Messages
 * https://developer.salesforce.com/docs/atlas.en-us.live_agent_rest.meta/live_agent_rest/live_agent_rest_Messages.htm
 * @package App\Model\Api\Sfla\Transaction
 */
class Messages extends \App\Model\Api\Sfla\Transaction
{
    /**
     * Request method
     */
    const METHOD = \App\Model\Request\I\Base::GET;

    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETE = 'complete';

    /**
     * Request path
     */
    const CONVERSATION_URL = '/chat/rest/System/Messages';

    /**
     * Required headers for the transaction
     * @var array
     */
    protected $requiredHeaders = [
        'X-LIVEAGENT-API-VERSION',
        'X-LIVEAGENT-AFFINITY',
        'X-LIVEAGENT-SESSION-KEY',
    ];

    /**
     * @param string $url
     * @return \App\Model\Varien\DataObject
     * @throws \Exception
     */
    public function process(string $url)
    {
        $response = parent::process($url);

        if (in_array($this->getResponse()->getCode(), [403]))
            throw new \App\Model\Exception($this->getResponse()->getBody());

        return $response;
    }
}