<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Sfla\Transaction;

use App\Model\Config;

/**
 * Class CaseCreate described Sfla Api transaction: Case
 * https://developer.salesforce.com/docs/atlas.en-us.api.meta/api/sforce_api_objects_case.htm
 * Example: https://blog.bergin.io/salesforce-rest-api-create-case-819609a5282a
 * @package App\Model\Api\Sfla\Transaction
 */
class CaseCreate extends \App\Model\Api\Sfla\Transaction
{
    /**
     * Method POST
     */
    const METHOD = \App\Model\Request\I\Base::POST;


    /**
     * Required headers
     * @var array
     */
    protected $requiredHeaders = [
        'Authorization',
        'Content-Type'
    ];

    protected $accessToken;
    protected $bodyRequest;
    protected $contactId;

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function setContactId(string $contactId): void
    {
        $this->contactId = $contactId;
    }

    public function setBodyRequest(array $bodyRequest): void
    {
        $this->bodyRequest = $bodyRequest;
    }

    /**
     * @param string $url
     * @return \App\Model\Varien\DataObject
     * @throws \Exception
     */
    public function process(string $url)
    {
        $url .= '/services/data/v' . Config::get('sfla.api_version') . '/sobjects/Case';

        if (!$this->validateBody()) {
            throw new \App\Model\Exception("Error: insufficient data");
        }
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json'
        ];
        $this->setHeaders($headers);
        $this->setRequest(json_encode($this->buildRequest()));

        return parent::process($url);
    }

    /**
     * Check if all body variables are present
     * @return bool
     */
    protected function validateBody(): bool
    {
        foreach (Config::get('sfla.variablesCase') as $key => $val) {
            if (!isset($this->bodyRequest[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Build get request
     * @return array
     * @throws \Exception
     */
    private function buildRequest()
    {
        return [
            'ContactId' => $this->contactId,
            'Subject' => $this->bodyRequest['INQUIRY'],
            'Comments' => strip_tags($this->bodyRequest['TRANSCRIPT']),
            'Origin' => 'Web'
        ];
    }
}
