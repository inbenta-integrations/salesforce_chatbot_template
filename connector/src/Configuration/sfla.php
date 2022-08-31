<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

return [
    'endpoint' => $_ENV['ENDPOINT'] ?? '', //Do not include "/chat/rest/" in the endpoint string
    'organizationId' => $_ENV['ORGANIZATION_ID'] ?? '',
    'deploymentId' => $_ENV['DEPLOYMENT_ID'] ?? '',
    'buttonId' => $_ENV['BUTTON_ID'] ?? '',

    // API config
    'api_endpoint' => $_ENV['API_ENDPOINT'] ?? '',
    'api_version' => $_ENV['API_VERSION'] ?? '55.0',
    'client_id' => $_ENV['CLIENT_ID'] ?? '',
    'client_secret' => $_ENV['CLIENT_SECRET'] ?? '',
    'username' => $_ENV['USERNAME'] ?? '',
    'password' => $_ENV['PASSWORD'] ?? '',

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
