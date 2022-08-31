<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Sfla\Transaction;

use App\Model\Config;

/**
 * Class ContactSave described Sfla Api transaction: ContactSave
 * 
 * @package App\Model\Api\Sfla\Transaction
 */
class ContactSave extends \App\Model\Api\Sfla\Transaction
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

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
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
        $url .= '/services/data/v' . Config::get('sfla.api_version') . '/sobjects/Contact';

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
        foreach (Config::get('sfla.variablesContact') as $key => $val) {
            if (!isset($this->bodyRequest[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Build get request
     * @return array
     */
    private function buildRequest(): array
    {
        $request = [];
        foreach (Config::get('sfla.variablesContact') as $key => $val) {
            $request[$val] = $this->bodyRequest[$key];
        }
        return $request;
    }
}
