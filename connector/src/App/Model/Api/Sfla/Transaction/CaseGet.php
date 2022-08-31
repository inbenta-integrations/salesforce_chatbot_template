<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Sfla\Transaction;

use App\Model\Config;

/**
 * Class CaseGet described Sfla Api transaction: Case
 * https://developer.salesforce.com/docs/atlas.en-us.api.meta/api/sforce_api_objects_case.htm
 * @package App\Model\Api\Sfla\Transaction
 */
class CaseGet extends \App\Model\Api\Sfla\Transaction
{
    /**
     * Method GET
     */
    const METHOD = \App\Model\Request\I\Base::GET;


    /**
     * Required headers
     * @var array
     */
    protected $requiredHeaders = [
        'Authorization',
        'Content-Type'
    ];

    protected $accessToken;
    protected $caseId;

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function setCaseId(string $caseId): void
    {
        $this->caseId = $caseId;
    }

    /**
     * @param string $url
     * @return \App\Model\Varien\DataObject
     * @throws \Exception
     */
    public function process(string $url)
    {
        $url .= '/services/data/v' . Config::get('sfla.api_version') . '/sobjects/Case/' . $this->caseId;

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json'
        ];
        $this->setHeaders($headers);

        return parent::process($url);
    }
}
