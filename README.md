# SALES FORCE INTEGRATION

### TABLE OF CONTENTS
* [OBJECTIVE](#objective)
* [FUNCTIONALITIES](#functionalities)
* [INSTALLATION](#installation)
* [DEPENDENCIES](#dependencies)

### OBJECTIVE
Salesforce Live Agent adapter connector tries to set a connection between Inbenta Chatbot and Salesforce Live Agent

More information:
- [Salesforce Live Agent Documentation](https://developer.salesforce.com/docs/atlas.en-us.live_agent_rest.meta/live_agent_rest)
- [Inbenta Chatbot Documentation](https://developers.inbenta.io/chatbot/chatbot-overview)

### FUNCTIONALITIES
The connector will have the following functionalities:
- **Check agents availability** in order to know whether there are agents online or not
- **Initialize a chat conversation** between the user and the agent
- **Send messages** from the user to the agent
- **Receive messages** from the agent


### INSTALLATION

#### Core application (back-end)
Use the files in `/connector` folder as back-end application: this is a middleware connector with Salesforce Live Agent.

1. Go to `/connector/src/Configuration/sfla.php` and set your Salesforce Live Agent (SFLA) configuration credentials.
2. Go to `/connector/src/Configuration/redis.php` and set your Redis configuration URL.
3. Host all files in `/connector` in your server.

##### Debugging
Some events are prepared to be tracked. You can easily enable/disable logs from `/connector/src/Configuration/log.php`


#### View (front-end)
Find the core application in `/sdk` folder.

Inside you will find a JS adapter that is used by the Chatbot integration. The `/sdk/example/index.html` has an example of how to use the JS adapter. In order to use it:
1. Set the Inbenta Chatbot SDK authentication using `sdkAuth` variable.
2. Set the Salesforce connector adapter configuration in `salesforceConf` variable.
    1. The `endpoint` is the path where the Core application is hosted. Make sure it is poiting to the folder `/connector`.
    Example: <https://<salesforce-connector-api-endpoint>/connector>
    2. The `agentName` will be used in case the agent does not have a name set in Salesforce. This is the name that will see the user.
    3. The `agentWaitTimeout` is the maximum time that user will be waiting for agent to connect. If no agent accept the chat invitation within this time, the invitation will be revoked and no-agents message will be displayed.
3. Review the Inbenta Chatbot SDK configuration, specially `chatbotId` and `environment`.

##### Debugging
In case you want to debug, inside the `/public/adapter/salesforce-connector-adapter.js` there's a `defaultSalesforceConf` object. There you can change the `debug` parameter to `true` in order to see most of the events in browser's console.


### DEPENDENCIES
This application uses these dependencies loaded through Composer:

    "php": ~7.3
    "predis/predis": "1.0.3",
  	"monolog/monolog": "^2.0",
  	"ext-mbstring": "*"
