<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

return [
    'endpoint'       => '', //Do not include "/chat/rest/" in the endpoint string
    'organizationId' => '',
    'deploymentId'   => '',
    'buttonId'       => '',

    // Variables names from Backstage and its assignation to Salesforce
    'variablesContact' => [ //FirstName, LastName and Email are mandatory
        'FIRST_NAME' => 'FirstName',
        'LAST_NAME' => 'LastName',
        'EMAIL_ADDRESS' => 'Email'
    ],
    'variablesCase' => [ //Subject is mandatory
        'INQUIRY' => 'Subject'
    ],
    'variablesAccount' => [ //Non mandatory, empty array if not used ([])
        'ACCOUNT_NAME' => 'Name'
    ],
    'nameToShow' => 'FIRST_NAME' // Name to show in Chat
];
