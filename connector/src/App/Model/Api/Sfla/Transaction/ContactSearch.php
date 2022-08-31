<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Sfla\Transaction;

use App\Model\Config;

/**
 * Class ContactSearch described Sfla Api transaction: ContactSearch
 * 
 * @package App\Model\Api\Sfla\Transaction
 */
class ContactSearch extends \App\Model\Api\Sfla\Transaction
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
    protected $contactEmail;

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function setEmail(string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * @param string $url
     * @return \App\Model\Varien\DataObject
     * @throws \Exception
     */
    public function process(string $url)
    {
        $url .= '/services/data/v' . Config::get('sfla.api_version') . '/query';
        $url .= "?q=SELECT+id,name,email+from+Contact+where+Email='" . $this->contactEmail . "'+limit+1";

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json'
        ];
        $this->setHeaders($headers);

        return parent::process($url);
    }
}
