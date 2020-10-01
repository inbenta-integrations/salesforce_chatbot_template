<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Sfla\Transaction;

use App\Model\Config;

/**
 * Class Availability described Sfla Api transaction: Availability
 * https://developer.salesforce.com/docs/atlas.en-us.live_agent_rest.meta/live_agent_rest/live_agent_rest_Availability.htm
 * @package App\Model\Api\Sfla\Transaction
 */
class Availability extends \App\Model\Api\Sfla\Transaction
{
    /**
     * Method GET
     */
    const METHOD = \App\Model\Request\I\Base::GET;

    /**
     * url path
     */
    const CONVERSATION_URL = '/chat/rest/Visitor/Availability';

    /**
     * Required headers
     * @var array
     */
    protected $requiredHeaders = [
        'X-LIVEAGENT-API-VERSION'
    ];

    /**
     * @param string $url
     * @return \App\Model\Varien\DataObject
     * @throws \Exception
     */
    public function process(string $url)
    {
        $this->setRequest($this->buildRequest());
        return parent::process($url);
    }

    /**
     * Build get request
     * @return array
     * @throws \Exception
     */
    private function buildRequest()
    {
        return [
            'org_id'              => Config::get('sfla.organizationId'),
            'deployment_id'       => Config::get('sfla.deploymentId'),
            'Availability.ids'    => '[' . Config::get('sfla.buttonId') . ']',
            'Availability.prefix' => 'Visitor',
        ];
    }
}