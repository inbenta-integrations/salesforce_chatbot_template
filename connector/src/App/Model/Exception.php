<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model;

/**
 * Class Exception, main app exception catcher
 * @package App\Model
 */
class Exception extends \Exception
{
    const E_UNKNOWN = 'Unknown error';
    const E_INBENTA_UNAUTHORIZED = 'Unauthorized on Inbenta';
    const E_KEY_AFFINITY_TOKEN = 'There was an error on receiving chat Key or Affinity Token from SFLA';
    const E_APIS_RESPONSE = 'Apis received status not equal 200';

    const E_AUTHORIZATION_REQUIRED = 'Header Authorization is required';
    const E_INBENTA_KEY_REQUIRED = 'Header X-Inbenta-Key is required';
    const E_ADAPTER_SESSION_REQUIRED = 'Header X-Adapter-Session-Id is required';
    const E_INBENTA_SESSION_REQUIRED = 'Header X-Inbenta-Session is required';
    const E_ORIGIN_REQUIRED = 'Header Origin is required';

    const E_PARAM_ACK_REQUIRED = 'Param \'ack\' is empty, the field is required as an integer';
    const E_SESSION_HAS_FINISHED = 'sflaKey and sflaAffinityToken should contain info, probably session was finished by another transaction';
    const E_LOCKER_HAS_UNDEFINED_STATUS = 'Locker has undefined status';
    const E_WRONG_LOCKER_TYPE = 'Value should be a DataObject type';
    const E_UPLOAD_URL_ERROR = 'Upload URL error';
    /**
     * @var string request/response json
     */
    public $data;

    /**
     * @var string json message and logId
     */
    public $response;
}
