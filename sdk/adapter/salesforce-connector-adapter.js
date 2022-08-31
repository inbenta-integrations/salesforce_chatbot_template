(function() {
  'use strict';
  window.salesforceConnectorAdapter = function(salesforceConf) {

    return function(chatbot) {

      let chatbotAdapter;
      let request = {
        messageBody: [],
        getMessage: []
      };
      let timers = {
        startChatTimer: 0,
        sendMessage: undefined
      };
      let listener = {
        chasitorTyping: false
      };
      let flag = {
        isActive: 'isActive',
        adapterSessionId: 'adapterSessionId',
        sequence: -1
      };

      // Default Salesforce chat configuration
      let defaultSalesforceConf = {
        debug: false,
        endpoint: 'https://<salesforce-connector-api-endpoint>',
        agentName: 'Agent',
        agentWaitTimeout: 60, // seconds
        sendMessagesTimeout: 30, // seconds
        sendMessagesDelay: 2, // seconds
        inputId: '#inbenta-bot-input',
        labels: {
          waitingForFile: 'Agent is requesting you a file (click on link icon)',
          uploadingFile: 'Uploading file, please wait',
          fileUploaded: 'File uploaded correctly',
          fileUploadError: 'Error on file upload',
          caseCreated: 'A new case was created',
          caseNumber: 'case number',
          caseError: 'Error on create a case, try again later'
        }
      }

      let config = extend(defaultSalesforceConf, salesforceConf);

      // Clean domain path
      let domain = config.endpoint.replace(/\/$/, '');

      // Full API paths
      let path = {
        auth: domain + '/init',
        getMessage: domain + '/message/receive',
        sendMessage: domain + '/message/send',
        availability: domain + '/availability',
        sendFile: domain + '/message/file',
        createCase: domain + '/createCase'
      };

      // File transfer variables
      let fileTransfer = {
        fileToken: '',
        uploadServletUrl: ''
      };

      // Define Chatbot messages to be used
      let chatbotMessage = {
        chatClosed: function() {
          chatbot.actions.displaySystemMessage({
            message: 'chat-closed',
            translate: true
          });
        },
        waitForAgent: function() {
          chatbot.actions.displaySystemMessage({
            message: 'wait-for-agent',
            translate: true
          });
        },
        agentLeft: function() {
          chatbot.actions.displaySystemMessage({
            message: 'agent-left',
            replacements: { agentName: AgentSession.get('name') || 'Agent' },
            translate: true
          });
        },
        agentJoined: function() {
          chatbot.actions.displaySystemMessage({
            message: 'agent-joined',
            replacements: { agentName: AgentSession.get('name') || 'Agent' },
            translate: true
          });
        },
        noAgents: function() {
          chatbot.actions.sendMessage({
            directCall: 'escalationNoAgentsAvailable'
          });
        },
        enterQuestion: function() {
          chatbot.actions.displayChatbotMessage({
            type: 'answer',
            message: 'enter-question',
            translate: true
          });
        },
        waitingForFile: function() {
          chatbot.actions.displaySystemMessage({
            message: defaultSalesforceConf.labels.waitingForFile
          });
        },
        uploadingFile: function() {
          chatbot.actions.displaySystemMessage({
            message: defaultSalesforceConf.labels.uploadingFile
          });
        },
        fileUploaded: function() {
          chatbot.actions.displaySystemMessage({
            message: defaultSalesforceConf.labels.fileUploaded
          });
        },
        fileUploadError: function() {
          chatbot.actions.displaySystemMessage({
            message: defaultSalesforceConf.labels.fileUploadError
          });
        }
      }

      // Define agent class
      let AgentSession = {
        storageId: '',
        session: false,
        errorCounter: 0,
        id: function(id) {
          this.storageId = id;
        },
        get: function(index) {
          let data = JSON.parse(localStorage.getItem(this.storageId));
          if (typeof data !== 'object' || data === null) {
            data = {};
            this.set(null, data);
          }

          return index ? data[index] : data;
        },
        set: function(index, value) {
          let data = '';
          if (index) {
            data = this.get();
            data[index] = value;
          } else {
            data = value;
          }
          localStorage.setItem(this.storageId, JSON.stringify(data));
        },
        clear: function() {
          localStorage.removeItem(this.storageId);
        }
      };

      chatbot.subscriptions.onStartConversation(function(conversationData, next) {
        AgentSession.id(conversationData.sessionId);
        return next();
      });

      chatbot.subscriptions.onResetSession(function(next) {
        AgentSession.clear();
        return next();
      });

      // Merge 2 objects
      function extend(destination, source) {
        for (let property in source) {
          if (source.hasOwnProperty(property)) {
            if (destination[property] && (typeof (destination[property]) === 'object') &&
              (destination[property].toString() === '[object Object]') && source[property]) {
              extend(destination[property], source[property]);
            } else {
              destination[property] = source[property];
            }
          }
        }
        return destination;
      }

      var resetErrorCounter = function() {
        AgentSession.errorCounter = 0;
      }

      // XMLHTTP basic GET/POST request
      let requestCall = function(requestOptions, responseHandler) {
        let xmlhttp = new XMLHttpRequest();
        let options = extend({
          type: 'POST',
          url: '',
          headers: { 'Content-Type': 'application/json; charset=utf-8' },
          data: ''
        }, requestOptions);

        xmlhttp.onload = function() {
          dd('Request response', xmlhttp.status, xmlhttp.response);
          let index = request.getMessage.indexOf(xmlhttp);
          if (index > -1) {
            request.getMessage.splice(index, 1);
          }
          if (typeof responseHandler !== 'undefined') {
            let responseBody = xmlhttp.response ? JSON.parse(xmlhttp.response) : {};
            responseHandler(xmlhttp.status, responseBody);
          }
        };

        xmlhttp.onerror = function() {
          dd('Request ERROR response', xmlhttp.status, xmlhttp.response);
        };

        xmlhttp.open(options.type, options.url, true);

        xmlhttp.timeout = config.sendMessagesTimeout * 1000; // 30 seconds

        xmlhttp.ontimeout = function(e) {
          retrieveSalesforceEvents();
        };

        for (let key in options.headers) {
          if (options.headers.hasOwnProperty(key)) {
            xmlhttp.setRequestHeader(key, options.headers[key]);
          }
        }
        dd('Request params', options);

        xmlhttp.send(options.data || null);

        return xmlhttp;
      };

      let checkAgentsAvailability = function(checkAgentsCallback) {

        let callback = function(code, response) {
          checkAgentsCallback(response);
        };

        let options = {
          type: 'GET',
          url: path.availability
        };

        requestCall(options, callback);
      };

      // Initialize Salesforce connection
      let initSalesforceConnection = function(params) {
        createSalesforceChat(params, function(response) {
          AgentSession.set(flag.isActive, true);
          AgentSession.set(flag.adapterSessionId, response.adapterSessionId);
          retrieveSalesforceEvents();
        });
      };

      // Create Salesforce chat
      let createSalesforceChat = function(params, callback) {

        chatbotMessage.waitForAgent();

        let headers = {
          'Content-Type': 'application/x-www-form-urlencoded charset=utf-8',
          'X-Inbenta-Key': chatbot.api.apiAuth.inbentaKey,
          'Authorization': chatbot.api.apiAuth.authorization.token,
          'X-Inbenta-Session': 'Bearer ' + chatbot.api.sessionToken
        };

        let options = {
          type: 'POST',
          url: path.auth,
          headers: headers,
          data: JSON.stringify(params)
        };

        let responseHandler = function(code, resp) {
          switch (code) {
            case 200:
            case 304:
              if (resp.success) {
                callback(resp);
                return;
              } else {
                endChatSession(false, 'Reason: Adapter received failure response (Create Chat)');
                chatbotMessage.noAgents();
              }
              break;
            default:
              endChatSession(false, 'Reason: Adapter received failure response (Create Chat)');
              chatbotMessage.noAgents();
          }
        };
        requestCall(options, responseHandler);
      };

      // Get Salesforce events
      let retrieveSalesforceEvents = function() {
        dd('retrieveSalesforceEvents')
        let ack = AgentSession.get('ack');
        if (typeof ack !== 'number') ack = 0;
        AgentSession.set('ack', ++ack);

        let headers = {
          'X-Adapter-Session-Id': AgentSession.get(flag.adapterSessionId)
        };

        let options = {
          type: 'GET',
          url: path.getMessage + '?ack=' + encodeURIComponent(ack),
          headers: headers
        };

        let responseHandler = function(code, resp) {
          switch (code) {
            case 204:
              resetErrorCounter();
              break;
            case 205:
              return;
            case 200:
              resetErrorCounter();
              if (!resp.success) {
                endChatSession(true, 'Reason: Adapter received failure response (Retrieve SF data)');
                chatbotMessage.noAgents();
                return;
              }
              if (resp.success && resp.data.messages instanceof Object) {
                resp.data.messages.forEach(function(message) {
                  switch (message.type) {
                    case 'AgentNotTyping':
                      chatbot.actions.hideChatbotActivity();
                      break;
                    case 'AgentTyping':
                      chatbot.actions.displayChatbotActivity();
                      break;
                    case 'ChatEstablished':
                      chatbotAdapter = true;
                      AgentSession.set('name', message.message.name);
                      chatbotMessage.agentJoined();
                      clearTimeout(timers.startChatTimer);
                      chatbot.api.track('CHAT_ATTENDED', { 'sflaId': resp.adapterSessionId });
                      retrieveLastMessages();
                      break;
                    case 'ChatMessage':
                      if (resp.data.sequence <= flag.sequence) {
                        dd('Duplicate message sequence, break', resp.data);
                        break;
                      }
                      flag.sequence = resp.data.sequence;
                      dd('ChatMessage', message.message.text);
                      chatbot.actions.hideChatbotActivity();
                      chatbot.actions.displayChatbotMessage({
                        type: 'answer',
                        message: message.message.text
                      });
                      break;
                    case 'ChatRequestFail':
                      chatbot.api.track('CHAT_UNATTENDED');
                      clearTimeout(timers.startChatTimer);
                      endChatSession(true, 'Reason: Adapter received "ChatRequestFail" response from Salesforce');
                      chatbotMessage.noAgents();
                      break;
                    case 'AgentDisconnect':
                    case 'ChatEnded':
                      endChatSession(true, 'Reason: Adapter received "ChatEnded" response from Salesforce');
                      chatbot.actions.hideUploadMediaButton();
                      chatbotMessage.agentLeft();
                      chatbotMessage.chatClosed();
                      chatbotMessage.enterQuestion();
                      break;
                    case 'ChatTransferred':
                      chatbotMessage.agentLeft();
                      AgentSession.set('name', message.message.name);
                      chatbotMessage.agentJoined();
                      break;
                    case 'FileTransfer':
                      if (message.message.type == 'Requested') {
                        setFileTransferOptions(message.message.fileToken, message.message.uploadServletUrl);
                        chatbot.actions.showUploadMediaButton();
                        chatbotMessage.waitingForFile();
                      }
                      else { //File transfer canceled or completed
                        setFileTransferOptions('', '');
                        chatbot.actions.hideUploadMediaButton();
                      }
                      break;
                    case 'ChasitorSessionData':
                    case 'ChatRequestSuccess':
                    case 'CustomEvent':
                    case 'NewVisitorBreadcrumb':
                    case 'QueueUpdate':
                      dd('missingEvent - ' + message.type, message.message);
                      break;
                  }
                })
              }
              break;
            case 401:
              endChatSession(true, 'Reason: Adapter received 401 code, It means session validation fails');
              chatbotMessage.chatClosed();
              chatbotMessage.enterQuestion();
              return;
            case 409:
              return;
            case 403:
              dd('Request timed out');
              return;
            default:
              AgentSession.errorCounter++;
          }
          if (AgentSession.errorCounter >= 3) {
            dd('Error counter: ' + AgentSession.errorCounter);
            endChatSession(true, 'Reason: Adapter received to many errors. Error counter limit');
            chatbotMessage.chatClosed();
            chatbotMessage.enterQuestion();
          } else {
            if (chatbotAdapter) {
              retrieveSalesforceEvents();
            }
          }
        };
        request.getMessage.push(requestCall(options, responseHandler));
      };

      // Send message to Salesforce agent
      let sendMessageToLiveAgent = function(message, callback) {
        dd('sendMessageToLiveAgent', message);
        if (message.noun !== 'ChatMessage') {
          let options = {
            type: 'POST',
            url: path.sendMessage,
            headers: {
              'X-Adapter-Session-Id': AgentSession.get(flag.adapterSessionId)
            },
            data: JSON.stringify([message])
          };
          requestCall(options, callback);
        } else {

          if (timers.sendMessage) clearTimeout(timers.sendMessage);

          request.messageBody.push(message);

          timers.sendMessage = setTimeout(function() {
            let options = {
              type: 'POST',
              url: path.sendMessage,
              headers: {
                'X-Adapter-Session-Id': AgentSession.get(flag.adapterSessionId)
              },
              data: JSON.stringify(request.messageBody)
            };
            request.messageBody = [];
            requestCall(options, function(code) {
              listener.chasitorTyping = false;
              if (code === 403 && AgentSession.get(flag.isActive)) {
                endChatSession(true, 'Reason: Adapter received 403 code, It means session validation fails');
                chatbotMessage.chatClosed();
                chatbotMessage.enterQuestion();
              }
            });
          }, config.sendMessagesDelay * 1000);
        }
      };

      // End chat session
      let endChatSession = function(sendNotice, reason) {
        reason = reason !== undefined ? reason : '';
        dd('endChatSession', reason);
        chatbotAdapter = false;
        AgentSession.set(flag.isActive, false);

        request.getMessage.forEach(function(request) {
          request.abort()
        });
        flag.sequence = -1;
        request.getMessage = [];
        let callback = function() {
          chatbot.actions.hideChatbotActivity();
          chatbot.actions.enableInput();
        };
        if (sendNotice) {
          sendMessageToLiveAgent({
            prefix: 'Chasitor',
            noun: 'ChatEnd',
            object: { 'type': 'ChatEndReason', 'reason': 'client' }
          }, callback);
        }
      };

      // Get Chatbot transcript
      let retrieveLastMessages = function() {
        dd('retrieveLastMessages')
        let message = {
          'prefix': 'Chasitor',
          'noun': 'ChatMessage',
          'object': { 'text': '-- PREVIOUS USER CONVERSATION --' }
        };
        sendMessageToLiveAgent(message);
        chatbot.actions.getConversationTranscript()
          .forEach(function(messageObj) {
            let author = '';
            switch (messageObj.user) {
              case 'guest':
                author = 'Client';
                break;
              default:
                author = 'ChatBot';
            }
            let historyMesage = {
              'prefix': 'Chasitor',
              'noun': 'ChatMessage',
              'object': { 'text': 'History ' + author + ': ' + messageObj.message }
            };
            sendMessageToLiveAgent(historyMesage);
          });
      };

      // Define chasitor events from Salesforce
      let chasitorEvent = function(listenerID, targetElement, chatbotInstance) {
        if (!chatbotAdapter) return;
        let message;
        let inputLength = targetElement[0].value.length;
        if (inputLength > 0 && !listener.chasitorTyping) {
          listener.chasitorTyping = true;
          message = { 'prefix': 'Chasitor', 'noun': 'ChasitorTyping', 'object': {} };
        } else if (inputLength <= 0 && listener.chasitorTyping && event.which !== 13) {
          listener.chasitorTyping = false;
          message = { 'prefix': 'Chasitor', 'noun': 'ChasitorNotTyping', 'object': {} };
        }
        if (!message) return;
        sendMessageToLiveAgent(message)
      };

      // Define file transfer options
      let setFileTransferOptions = function(fileToken, uploadServletUrl) {
        fileTransfer = {
          fileToken: fileToken,
          uploadServletUrl: uploadServletUrl
        };
      };

      let createCase = function(formData) {
        let headers = {
          'Content-Type': 'application/json',
        };

        let options = {
          type: 'POST',
          url: path.createCase,
          headers: headers,
          data: JSON.stringify(formData)
        };
        let callback = function(code, response) {
          let messageResponse = defaultSalesforceConf.labels.caseError;
          if (code === 200) {
            if (response.success) {
              messageResponse = defaultSalesforceConf.labels.caseCreated;
              if (response.case !== undefined || response.case !== '') {
                messageResponse += ' (' + defaultSalesforceConf.labels.caseNumber + ': ' + response.case + ')';
              }
            } else {
              messageResponse = response.data.message !== undefined ? response.data.message : messageResponse
            }
          }
          chatbot.actions.hideChatbotActivity();
          chatbot.actions.enableInput();
          chatbot.actions.displaySystemMessage({
            message: messageResponse
          });
        };

        requestCall(options, callback);
      };

      let getTranscript = function() {
        let conversation = chatbot.actions.getConversationTranscript();
        let fullConversation = '';
        for (let message of conversation) {
          if (message.message !== '') {
            fullConversation += message.user === 'assistant' ? 'ChatBot' : 'Client';
            fullConversation += ': ' + message.message.trim() + "\n";
          }
        }
        if (fullConversation !== '') {
          fullConversation = "*TRANSCRIPT*\n\n" + fullConversation;
        }
        return fullConversation;
      }

      // Detect escalationOffer content
      chatbot.subscriptions.onDisplayChatbotMessage(function(messageData, next) {
        if ('attributes' in messageData && messageData.attributes !== null && 'DIRECT_CALL' in messageData.attributes && messageData.attributes.DIRECT_CALL === 'escalationOffer') {
          checkAgentsAvailability(function(response) {
            chatbot.api.addVariable('agents_available', response.isAvailable.toString());
          });
        } else if ('actions' in messageData && messageData.actions.length > 0) {
          for (let i = 0; i < messageData.actions.length; i++) {
            if (!("parameters" in messageData.actions[i])) continue;
            if (!("callback" in messageData.actions[i].parameters)) continue;

            if (messageData.actions[i].parameters.callback == 'createTicket') {
              chatbot.actions.disableInput();
              chatbot.actions.displayChatbotActivity();
              let formData = messageData.actions[i].parameters.data;
              formData.TRANSCRIPT = getTranscript();
              createCase(formData);
            }
          }
        }
        return next(messageData);
      });

      // Start live chat connection
      chatbot.subscriptions.onEscalateToAgent(function(escalationData, next) {
        let checkAgentsCallback = function(response) {
          if (!response.isAvailable) {
            chatbotMessage.noAgents();
            return;
          }
          chatbotAdapter = true;
          timers.startChatTimer = setTimeout(function() {
            if (!chatbotAdapter) return;
            endChatSession(true, 'Reason: Timeout. No agent answer');
            chatbotMessage.noAgents();
          }, config.agentWaitTimeout * 1000);
          initSalesforceConnection(escalationData)
        }

        checkAgentsAvailability(checkAgentsCallback);
      });

      // Trigger escalation start
      chatbot.subscriptions.onEscalationStart(function(escalationData, next) {
        chatbot.actions.sendMessage({ directCall: 'escalationStart' });
      });

      // Decide whether the message has to be sent to Chatbot or Live Agent
      chatbot.subscriptions.onSendMessage(function(messageData, next) {
        if (chatbotAdapter) {
          let message = {
            'prefix': 'Chasitor',
            'noun': 'ChatMessage',
            'object': { 'text': messageData.message }
          };
          if (messageData.message !== '') sendMessageToLiveAgent(message);
        } else {
          AgentSession.errorCounter = 0;
          return next(messageData);
        }
      });

      // On Chatbot ready check if there was a conversation with an agent
      chatbot.subscriptions.onDomReady(function(next) {
        chatbot.helpers.setListener(config.inputId, 'keyup', chasitorEvent, chatbot);
        if (chatbot.actions.getSessionData().sessionId) {
          AgentSession.id(chatbot.actions.getSessionData().sessionId);
          if (AgentSession.get(flag.isActive)) {
            chatbotAdapter = true;
            request.getMessage.forEach(function(request) { request.abort() });
            request.getMessage = [];
            retrieveSalesforceEvents();
          }
        }
        return next();
      });

      // End conversation if user closes Chatbot window
      chatbot.subscriptions.onSelectSystemMessageOption(function(optionData, next) {
        let isCloseButton = optionData.id === 'exitConversation';
        let isYesValue = optionData.option && optionData.option.value === 'yes';
        let isChatActive = AgentSession.get(flag.isActive);
        if (isCloseButton && isYesValue && isChatActive) {
          clearTimeout(timers.startChatTimer);
          endChatSession(true, 'Reason: Customer exited conversation');
          chatbotMessage.chatClosed();
          chatbotMessage.enterQuestion();
        } else {
          return next(optionData);
        }
      });

      // Make the process to upload selected file
      chatbot.subscriptions.onUploadMedia(function(media, next) {
        chatbotMessage.uploadingFile();

        let formData = new FormData();
        formData.append("file", media.file);
        formData.append('fileToken', fileTransfer.fileToken);
        formData.append('uploadServletUrl', fileTransfer.uploadServletUrl);

        let options = {
          type: 'POST',
          url: path.sendFile,
          data: formData,
          headers: { 'X-Adapter-Session-Id': AgentSession.get(flag.adapterSessionId) }
        };
        let xmlhttp = new XMLHttpRequest();

        xmlhttp.onload = function() {
          dd('Request response', xmlhttp.status, xmlhttp.response);
          let responseBody = xmlhttp.response ? JSON.parse(xmlhttp.response) : {};
          if (responseBody.success !== undefined && responseBody.success !== null) {
            if (responseBody.success) chatbotMessage.fileUploaded();
            else chatbotMessage.fileUploadError();
          }
          else {
            chatbotMessage.fileUploadError();
            chatbot.actions.hideUploadMediaButton();
          }
        };

        xmlhttp.onerror = function() {
          dd('Request ERROR response', xmlhttp.status, xmlhttp.response);
          chatbot.actions.hideUploadMediaButton();
          chatbotMessage.fileUploadError();
        };
        xmlhttp.open(options.type, options.url, true);

        xmlhttp.timeout = (config.sendMessagesTimeout + 20) * 1000; // 50 seconds

        xmlhttp.ontimeout = function(e) {
          chatbotMessage.fileUploadError();
        };

        for (let key in options.headers) {
          if (options.headers.hasOwnProperty(key)) {
            xmlhttp.setRequestHeader(key, options.headers[key]);
          }
        }

        xmlhttp.send(options.data || null);
        next();
      });

      // Debug function
      let dd = function() {
        if (config.debug) {
          let date = new Date(new Date().toUTCString()).toISOString();
          let dateColor = 'background: #222; color: #bada55';
          let title = (arguments[0]) ? arguments[0] : '';
          let titleColor = 'background: #222; color: white';
          let args = Array.prototype.slice.call(arguments);
          args.shift();
          args.unshift('%c' + date + '%c ' + title, dateColor, titleColor);
          console.log.apply(console, args);
        }
      };

    }
  }
})();
