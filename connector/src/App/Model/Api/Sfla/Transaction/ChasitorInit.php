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
        //'X-LIVEAGENT-SEQUENCE',
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
        $mainParams = $this->createPrechatElements($mainParams);

        return $mainParams;
    }

    /**
     * Creates the "prechatDetails" and "prechatEntities"
     * Structure example:
     * https://help.salesforce.com/s/articleView?id=000340657&type=1
     * @param array $mainParams
     * @return array $mainParams
     */
    private function createPrechatElements(array $mainParams): array
    {
        $variablesContact = Config::get('sfla.variablesContact');
        $variablesCase = Config::get('sfla.variablesCase');
        $variablesAccount = Config::get('sfla.variablesAccount');
        $nameToShow = Config::get('sfla.nameToShow');

        $fieldsCase = ['Status' => 'New', 'Origin' => 'Chat'];
        $fields = array_merge($this->preChatParams, $fieldsCase);

        foreach ($fields as $key => $value) {
            if ($key === $nameToShow) $mainParams['visitorName'] = $value;

            $mainParams['prechatDetails'][] = $this->prechatDetailsValues($key, $value, $variablesCase, $variablesContact, $variablesAccount);
        }
        $mainParams['prechatEntities'] = $this->prechatEntities($fieldsCase, $variablesContact, $variablesAccount);

        return $mainParams;
    }


    /**
     * Creates the values for prechat details
     * @param string $key
     * @param string $value
     * @param array $variablesCase
     * @param array $variablesContact
     * @param array $variablesAccount
     * @return array
     */
    private function prechatDetailsValues(string $key, string $value = null, array $variablesCase, array $variablesContact, array $variablesAccount = null): array
    {
        $label = $fieldName = $key;
        $type = 'Case';
        if (isset($variablesCase[$key])) {
            $label = $fieldName = $variablesCase[$key];
        } else if (isset($variablesContact[$key])) {
            $label = $fieldName = $variablesContact[$key];
            $type = 'Contact';
        } else if (isset($variablesAccount[$key])) {
            $fieldName = $variablesAccount[$key];
            $label = $fieldName == 'Name' ? 'AccountName' : $fieldName;
            $type = 'Account';
        }
        $transcriptField = 'c__' . $label;
        $value = is_null($value) || $value == '' ? 'No' . $label : $value; // Null or empty strings are not allowed

        return [
            'label' => $label,
            'value' => $value,
            'transcriptFields' => [$transcriptField],
            'displayToAgent' => true,
            'entityMaps' => [
                [
                    'entityName' => $type,
                    'fieldName' => $fieldName
                ]
            ]
        ];
    }

    /**
     * Array to send the instructions to create the Case, the Contact and the Account (if exists)
     * @param array $fieldsCase
     * @param array $fieldsContact
     * @param array $fieldsAccount = null
     * @return array
     */
    private function prechatEntities(array $fieldsCase, array $fieldsContact, array $fieldsAccount = null): array
    {
        $prechatEntities = [
            [
                'entityName' => 'Case',
                'showOnCreate' => true, // Show "Case" details when chat is opened
                'saveToTranscript' => 'Case',
                'entityFieldsMaps' => $this->prechatEntitiesCase($fieldsCase)
            ],
            [
                'entityName' => 'Contact',
                'showOnCreate' => true, // Show "Contact" details when chat is opened
                'saveToTranscript' => 'Contact',
                'linkToEntityName' => 'Case',
                'linkToEntityField' => 'ContactId',
                'entityFieldsMaps' => $this->prechatEntitiesContact($fieldsContact)
            ]
        ];
        if (is_array($fieldsAccount) && count($fieldsAccount) > 0) {
            $prechatEntities[] = $this->prechatEntitiesAccount($fieldsAccount);
        }
        return $prechatEntities;
    }

    /**
     * Create the "prechatEntities" structure for Case
     * @param array $fieldsCase
     * @return array $caseEntityFieldsMaps
     */
    private function prechatEntitiesCase(array $fieldsCase): array
    {
        if (!isset($fieldsCase['Subject'])) $fieldsCase['Subject'] = ''; //Create if not exists
        $caseEntityFieldsMaps = [];
        foreach ($fieldsCase as $field => $value) {
            $caseEntityFieldsMaps[] = [
                'fieldName' => $field,
                'label' => $field,
                'doFind' => false,
                'isExactMatch' => false,
                'doCreate' => true
            ];
        }
        return $caseEntityFieldsMaps;
    }

    /**
     * Create the "prechatEntities" structure for Contact
     * @param array $fieldsContact
     * @return array $contactEntityFieldsMaps
     */
    private function prechatEntitiesContact(array $fieldsContact): array
    {
        $contactEntityFieldsMaps = [];
        foreach ($fieldsContact as $field) {
            $contactEntityFieldsMaps[] = [
                'fieldName' => $field,
                'label' => $field,
                'doFind' => true,
                'isExactMatch' => true,
                'doCreate' => true,
            ];
        }
        return $contactEntityFieldsMaps;
    }

    /**
     * Create the "prechatEntities" structure for Account
     * @param array $fieldsContact
     * @return array $contactEntityFieldsMaps
     */
    private function prechatEntitiesAccount(array $fieldsAccount): array
    {
        $accountEntityFieldsMaps = [];
        foreach ($fieldsAccount as $field) {
            $label = $field == 'Name' ? 'AccountName' : $field;
            $accountEntityFieldsMaps[] = [
                'fieldName' => $field,
                'label' => $label,
                'doFind' => false,
                'isExactMatch' => true,
                'doCreate' => true
            ];
        }
        return [
            'entityName' => 'Account',
            'showOnCreate' => false, // No not show "Account" details when chat is opened
            'saveToTranscript' => 'Account',
            'linkToEntityName' => 'Case',
            'linkToEntityField' => 'AccountId',
            'entityFieldsMaps' => $accountEntityFieldsMaps
        ];
    }

    /**
     * @param array $preChatParams
     */
    public function setPreChatParams(array $preChatParams)
    {
        $this->preChatParams = $preChatParams;
    }
}
