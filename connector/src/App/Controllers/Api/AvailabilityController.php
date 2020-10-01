<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Controllers\Api;

use App\Model\Config;

class AvailabilityController extends Controller
{
    /**
     * Main check action
     * @throws \Exception
     */
    public function checkAction()
    {
        $response = new \App\Model\Request\Response\Curl();
        $requester = new \App\Model\Request\Curl($response);

        $availability = new \App\Model\Api\Sfla\Transaction\Availability($requester);
        // emulate client domain, in other case - salesforce throw an error 'domain is not in white list'
        $availability->addHeaders([
            'Origin'  => $this->getRequest()->getHeader('Origin'),
            'Referer' => $this->getRequest()->getHeader('Referer')
        ]);
        // get sfla response
        $sflaResponse = $availability->process(Config::get('sfla.endpoint'));
        // parse first item from answer
        $isAgentAvailable = $sflaResponse->getData('messages/0/message/results/0/isAvailable') ?? false;

        $this->getResponse()
            ->setSuccess(true)
            ->setData('isAvailable', $isAgentAvailable);
    }
}