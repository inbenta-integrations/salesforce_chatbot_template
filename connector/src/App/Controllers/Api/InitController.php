<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Controllers\Api;

use App\Model\Config;
use App\Model\Exception as AppEx;
use App\Model\Exception;
use App\Model\Session;

class InitController extends Controller
{
    /**
     * Main init action
     * @throws \Exception
     */
    public function initAction()
    {
        // get Inbenta apis
        $apisTransaction = $this->getInbentaApis($this->getRequest());
        $chatUrl = $apisTransaction->getData('apis/chatbot');

        // Validate inbenta session
        if (!$this->isValidInbentaSession($this->getRequest(), $chatUrl))
            throw new \App\Model\Api\Inbenta\Exception(AppEx::E_INBENTA_UNAUTHORIZED);

        // Create SFLA sessionId
        $sflaSession = $this->createSflaSession();
        if (!$sflaSession->getData('key') || !$sflaSession->getData('affinityToken'))
            throw $this->error(AppEx::E_KEY_AFFINITY_TOKEN);

        Session::setSession([
            'sflaId'            => $sflaSession->getData('id'),
            'sflaKey'           => $sflaSession->getData('key'),
            'sflaAffinityToken' => $sflaSession->getData('affinityToken'),
        ]);

        // Init chat with agent
        $key = $sflaSession->getData('key');
        $affinityToken = $sflaSession->getData('affinityToken');
        $this->chasitorInit($key, $affinityToken);

        $this->getResponse()
            ->setSuccess(true)
            ->setData('adapterSessionId', Session::getSessionId());
    }

    private function getInbentaApis(\App\Facade\I\Request $request)
    {
        $response = new \App\Model\Request\Response\Curl();
        $requester = new \App\Model\Request\Curl($response);
        $apis = new \App\Model\Api\Inbenta\Transaction\Apis($requester);

        $apis->addHeaders([
            'Authorization'     => $request->getHeader('Authorization'),
            'Origin'            => $request->getHeader('Origin'),
            'X-Inbenta-Key'     => $request->getHeader('X-Inbenta-Key')
        ]);

        return $apis->process(Config::get('inbenta.baseApiUrl'));
    }

    /**
     * Check Inbenta session
     * @param \App\Facade\I\Request $request
     * @param string $checkUrl
     * @return bool
     * @throws \Exception
     */
    private function isValidInbentaSession(\App\Facade\I\Request $request, string $checkUrl)
    {
        $response = new \App\Model\Request\Response\Curl();
        $requester = new \App\Model\Request\Curl($response);
        $inbentaSession = new \App\Model\Api\Inbenta\Transaction\Session($requester);

        $inbentaSession->addHeaders([
            'Authorization'     => $request->getHeader('Authorization'),
            'X-Inbenta-Key'     => $request->getHeader('X-Inbenta-Key'),
            'X-Inbenta-Session' => $request->getHeader('X-Inbenta-Session'),
        ]);

        return $inbentaSession->process($checkUrl);
    }

    /**
     * Create SFLA sessionId
     * @return \App\Model\Varien\DataObject
     * @throws \Exception
     */
    private function createSflaSession()
    {
        $response = new \App\Model\Request\Response\Curl();
        $requester = new \App\Model\Request\Curl($response);
        $session = new \App\Model\Api\Sfla\Transaction\SessionId($requester);

        $session->addHeaders([
            'X-LIVEAGENT-AFFINITY' => 'null',
        ]);

        return $session->process(Config::get('sfla.endpoint'));
    }

    /**
     * Init chat with agent
     * @param string $sessionKey
     * @param string $affinityToken
     * @return \App\Model\Varien\DataObject
     * @throws \Exception
     */
    private function chasitorInit(string $sessionKey, string $affinityToken)
    {
        $response = new \App\Model\Request\Response\Curl();
        $requester = new \App\Model\Request\Curl($response);
        $session = new \App\Model\Api\Sfla\Transaction\ChasitorInit($requester);

        $session->addHeaders([
            'X-LIVEAGENT-SESSION-KEY' => $sessionKey,
            'X-LIVEAGENT-AFFINITY'    => $affinityToken,
            'X-LIVEAGENT-SEQUENCE'    => 1,
        ]);

        $session->setPreChatParams($this->getRequest()->getBody()->getData());

        return $session->process(Config::get('sfla.endpoint'));
    }

    /**
     * Validate required headers
     * @param \App\Facade\I\Request $request
     * @throws Exception
     */
    protected function validateRequest(\App\Facade\I\Request $request)
    {

        parent::validateRequest($request);

         if (!$request->getHeader('Authorization')) {
             throw $this->error(AppEx::E_AUTHORIZATION_REQUIRED);
         }

         if (!$request->getHeader('Origin')) {
             throw $this->error(AppEx::E_ORIGIN_REQUIRED);
         }

         if (!$request->getHeader('X-Inbenta-Key')) {
             throw $this->error(AppEx::E_INBENTA_KEY_REQUIRED);
         }

         if (!$request->getHeader('X-Inbenta-Session')) {
             throw $this->error(AppEx::E_INBENTA_SESSION_REQUIRED);
         }

    }
}