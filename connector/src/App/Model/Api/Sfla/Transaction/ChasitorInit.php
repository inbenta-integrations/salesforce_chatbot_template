<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Sfla\Transaction;

use App\Model\Config;
use App\Model\Session;

/**
 * Class ChasitorInit described Sfla Api transaction: ChasitorInit
 * https://developer.salesforce.com/docs/atlas.en-us.live_agent_rest.meta/live_agent_rest/live_agent_rest_ChasitorInit.htm
 * @package App\Model\Api\Sfla\Transaction
 */
class ChasitorInit extends \App\Model\Api\Sfla\Transaction
{
    /**
     * Request method
     */
    const METHOD = \App\Model\Request\I\Base::POST;

    /**
     * Request path
     */
    const CONVERSATION_URL = '/chat/rest/Chasitor/ChasitorInit';

    /**
     * Required headers for the transaction
     * @var array
     */
    protected $requiredHeaders = [
        'X-LIVEAGENT-API-VERSION',
        'X-LIVEAGENT-AFFINITY',
        'X-LIVEAGENT-SESSION-KEY',
//        'X-LIVEAGENT-SEQUENCE',
    ];

    /**
     * prechat params, fill by js init function
     * @var array
     */
    protected $preChatParams = [];

    /**
     * @param string $url
     * @return \App\Model\Varien\DataObject
     * @throws \Exception
     */
    public function process(string $url)
    {
        $this->setRequest(json_encode($this->buildRequest()));
        return parent::process($url);
    }

    /**
     * Build post request
     * @return array
     * @throws \Exception
     */
    private function buildRequest()
    {
        $mainParams = [
            'organizationId'      => Config::get('sfla.organizationId'),
            'deploymentId'        => Config::get('sfla.deploymentId'),
            'buttonId'            => Config::get('sfla.buttonId'),
            'sessionId'           => Session::get('sflaId'),
            'userAgent'           => $_SERVER['HTTP_USER_AGENT'],
            'language'            => "en-US",
            'screenResolution'    => "2560x1440",
            'prechatDetails'      => [],
            'prechatEntities'     => [],
            'receiveQueueUpdates' => true,
            'isPost'              => true,
        ];

        foreach ($this->preChatParams as $key => $value) {
            // set name param as a chat title
            if ($key === 'name') $mainParams['visitorName'] = $value;

            $mainParams['prechatDetails'][] = [
                'label' => $key,
                'value' => $value,
                'transcriptFields' => [ 'c__' . $key ],
                'displayToAgent'   => true,
            ];
        }

        return $mainParams;
    }

    /**
     * @param array $preChatParams
     */
    public function setPreChatParams(array $preChatParams)
    {
        $this->preChatParams = $preChatParams;
    }
}