<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Api\Sfla\Transaction;

use App\Model\Config;

/**
 * Class OAuth described Oauth username-password:
 * https://help.salesforce.com/s/articleView?id=sf.remoteaccess_oauth_username_password_flow.htm&type=5
 * @package App\Model\Api\Sfla\Transaction
 */
class OAuth extends \App\Model\Api\Sfla\Transaction
{
    /**
     * Method POST
     */
    const METHOD = \App\Model\Request\I\Base::POST;

    /**
     * @param string $url
     * @return \App\Model\Varien\DataObject
     * @throws \Exception
     */
    public function process(string $url)
    {
        $url .= '/services/oauth2/token';
        $url .= '?grant_type=password';
        $url .= '&client_id=' . Config::get('sfla.client_id');
        $url .= '&client_secret=' . Config::get('sfla.client_secret');
        $url .= '&username=' . Config::get('sfla.username');
        $url .= '&password=' . Config::get('sfla.password');

        return parent::process($url);
    }
}
