<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Controllers\Api;

use App\Model\Api\Sfla\Transaction\Messages as SflaMessages;
use App\Model\Config;
use App\Model\Exception as AppEx;
use App\Model\Redis;
use App\Model\Session;
use App\Model\Varien\DataObject;

class MessageController extends Controller
{

    protected $uploadBaseUrl = 'salesforce.com/services/liveagent/file';

    /**
     * Main message/receive action
     * @throws \Exception
     */
    public function getAction()
    {
        $ack = $this->getRequest()->getQuery()->getData('ack');

        if (is_null($ack) || !preg_match("/^[0-9]+$/", $ack)) {
            throw $this->error(AppEx::E_PARAM_ACK_REQUIRED);
        }
        $key = Session::getSessionId() . "__" . $ack;
        $redis = new Redis();
        $redis = $redis->getRedisClient();

        $value = new DataObject([
            'status' => SflaMessages::STATUS_PROCESSING,
        ]);
        try {
            if (!$redis->setnx($key, serialize($value))) {
                /**
                 * If we can't create locker - it means another process already created,
                 * So we need to read it, and there are two statuses "complete", "processing"
                 * "complete" - just get value from locker and send it to client
                 * "processing" - subscribe ot the locker (master-request), observe and reproduce it's answer
                 */

                $redis->expire($key, Config::get('redis.ttl'));

                $value = unserialize($redis->get($key));
                if ($value instanceof DataObject) {

                    switch ($value->getData('status')) {
                            // just get value from locker and send it to client
                        case SflaMessages::STATUS_COMPLETE:
                            $this->getResponse()
                                ->setSuccess(true)
                                ->setData('code', $value->getData('code'))
                                ->setBody($value->getData());
                            return;
                            // all secondary transaction should subscribe to master-request response
                        case SflaMessages::STATUS_PROCESSING:
                            $redis->subscribe(['new-message' . $key], [$this, 'subscribeCallback']);
                            return;
                        default:
                            throw $this->error(AppEx::E_LOCKER_HAS_UNDEFINED_STATUS);
                    }
                } else {
                    throw $this->error(AppEx::E_WRONG_LOCKER_TYPE);
                }
                return;
            }

            $redis->expire($key, Config::get('redis.ttl'));

            if (!Session::get('sflaKey') || !Session::get('sflaAffinityToken')) {
                throw $this->error(AppEx::E_SESSION_HAS_FINISHED);
            }

            $response = new \App\Model\Request\Response\Curl();
            $requester = new \App\Model\Request\Curl($response);
            $getter = new SflaMessages($requester);

            $getter->addHeaders([
                'X-LIVEAGENT-SESSION-KEY' => Session::get('sflaKey'),
                'X-LIVEAGENT-AFFINITY'    => Session::get('sflaAffinityToken'),
            ]);
            $getter->setRequest(['ack' => $ack]);

            $messages = $getter->process(Config::get('sfla.endpoint'));
            $responseCode = $getter->getResponse()->getCode();

            switch ($responseCode) {
                case 200: // transaction complete correctly
                    $messages->setData('status', SflaMessages::STATUS_COMPLETE);
                    $messages->setData('code', $responseCode);
                    $messages->setData('ack', ++$ack);
                    $redis->setex($key, Config::get('redis.ttl'), serialize($messages));
                    break;
                case 204: // transaction complete without new data
                    // if session already closed on Salesforce, it response 204 anyway((
                    if (!Session::get('sflaKey') || !Session::get('sflaAffinityToken')) {
                        $messages->setData('status', SflaMessages::STATUS_COMPLETE);
                        $messages->setData('code', 403);
                        $redis->setex($key, Config::get('redis.ttl'), serialize($messages));
                        break;
                    }
                    $messages->setData('code', $responseCode);

                default:
                    $redis->del($key);
            }
        } catch (AppEx $ex) {
            $this->getResponse()
                ->setSuccess(false)
                ->setData('code', 200);
            $redis->publish('new-message' . $key, serialize($this->getResponse()));

            throw $ex;
        }
        $this->getResponse()
            ->setSuccess(true)
            ->setData('code', $messages->getData('code'))
            ->setBody($messages->getData());
        // publish to resolve subscribed transactions from chat
        $redis->publish('new-message' . $key, serialize($this->getResponse()));
    }

    /**
     * Callback function for redis subscribe
     * @param $redis
     * @param $channel
     * @param $response
     * @throws AppEx
     */
    public function subscribeCallback($redis, $channel, $response)
    {
        if (!$response instanceof DataObject) {
            throw new AppEx(AppEx::E_WRONG_LOCKER_TYPE);
        }

        if ($response->getData('code'))
            http_response_code($response->getData('code'));

        header("Content-type: application/json; charset=utf-8");
        echo $response->getJsonFormatted();
        Session::destroy();
        $redis->unsubscribe([$channel]);
        die();
    }

    /**
     * Main message/send action
     * @throws \Exception
     */
    public function postAction()
    {
        $responses = [];
        $messages = $this->getRequest()->getBody();

        $messagesList = $messages->getData();
        foreach ($messagesList as $i => $m) {
            if (isset($m['object']['text']))
                $messagesList[$i]['object']['text'] = strip_tags($m['object']['text']);
        }

        if ($messages->getData() < 1) {
            $this->getResponse()
                ->setSuccess(false)
                ->setBody('Request elements count less than 1');
            return;
        }

        $requester = new \App\Model\Request\Curl(
            new \App\Model\Request\Response\Curl()
        );

        $headers = [
            'X-LIVEAGENT-SESSION-KEY' => Session::get('sflaKey'),
            'X-LIVEAGENT-AFFINITY'    => Session::get('sflaAffinityToken'),
        ];

        if (count($messages->getData()) > 1) {
            //use MultiNoun transaction
            $batch = new \App\Model\Api\Sfla\Transaction\MultiNoun($requester);
            $batch->addHeaders($headers);
            //$batch->setRequest(json_encode(['nouns' => $messages->getData()]));
            $batch->setRequest(json_encode(['nouns' => $messagesList]));
            $responses[] = $batch->process(Config::get('sfla.endpoint'))->getData();
        } else {
            //use ChatMessage (single) transaction
            $message = new \App\Model\Varien\DataObject(current($messages->getData()));

            switch ($message->getData('noun')) {
                case 'ChasitorTyping':
                    $transaction = new \App\Model\Api\Sfla\Transaction\ChasitorTyping($requester);
                    break;
                case 'ChasitorNotTyping':
                    $transaction = new \App\Model\Api\Sfla\Transaction\ChasitorNotTyping($requester);
                    break;
                case 'ChatEnd':
                    try {
                        $redis = new Redis();
                        $redis = $redis->getRedisClient();
                        $keys = $redis->keys(Session::getSessionId() . "__*");
                        $redis->del($keys);
                    } catch (\Exception $es) {
                    }
                    if (!Session::get('sflaKey') || !Session::get('sflaAffinityToken')) {
                        $this->getResponse()
                            ->setData('code', 205)
                            ->setSuccess(true)
                            ->setBody(['ok']);
                        return;
                    }
                    $transaction = new \App\Model\Api\Sfla\Transaction\ChatEnd($requester);
                    $transaction->setRequest('{"reason":"client"}');

                    Session::setSession([
                        'sflaId'            => null,
                        'sflaKey'           => null,
                        'sflaAffinityToken' => null,
                    ]);
                    break;
                default:
                    $transaction = new \App\Model\Api\Sfla\Transaction\ChatMessage($requester);
                    $transaction->setRequest(json_encode(['text' => strip_tags($message->getData('object/text'))]));
            }
            $transaction->addHeaders($headers);
            $response = $transaction->process(Config::get('sfla.endpoint'));
            if ($response->getData('code')) {
                $this->getResponse()->setData('code', $response->getData('code'));
            }
            $responses[] = $response->getData();
        }

        $this->getResponse()
            ->setSuccess(true)
            ->setBody($responses);
    }

    /**
     * Validate required headers
     * @param \App\Facade\I\Request $request
     * @throws \Exception
     */
    protected function validateRequest(\App\Facade\I\Request $request)
    {
        parent::validateRequest($request);

        if (!$request->getHeader('X-Adapter-Session-Id')) {
            throw new \Exception(AppEx::E_ADAPTER_SESSION_REQUIRED);
        }
    }

    /**
     * Sends a file to Salesforce, from user to agent (requested by agent)
     */
    public function fileAction()
    {
        $postCurl = $this->checkUploadedFile();
        if (count($postCurl) > 0) {

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->fileUploadUrl(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $postCurl
            ]);
            $result = curl_exec($ch);
            curl_close($ch);
            @unlink($postCurl['file']->name);

            if ($result) {
                if ($result == 'Success') {
                    $this->getResponse()
                        ->setSuccess(true)
                        ->setBody(['message' => $result]);
                    return true;
                }
            }
        }
        $this->getResponse()
            ->setSuccess(false)
            ->setBody(['message' => 'upload_error']);
    }

    /**
     * Check if file is uploaded correctly and return the array for curl
     * @return array
     */
    protected function checkUploadedFile(): array
    {
        $fileName = $_FILES['file']['name'];
        $fileDir = sys_get_temp_dir() . '/' . $_FILES['file']['name'];
        if (move_uploaded_file($_FILES['file']['tmp_name'], $fileDir)) {
            $mimetype = mime_content_type($fileDir);
            return [
                'file' => curl_file_create($fileDir, $mimetype, $fileName),
                'filename' => $fileName
            ];
        }
        return [];
    }

    /**
     * Creates the url for upload file
     * @return string $uploadUrl
     */
    protected function fileUploadUrl(): string
    {
        $chatKey = explode("!", Session::get('sflaKey'))[0];
        $request = $this->getRequest()->getPost();
        $fileToken = isset($request['fileToken']) ? $request['fileToken'] : '';
        $uploadUrl = isset($request['uploadServletUrl']) ? $this->validateUploadUrl($request['uploadServletUrl']) : '';
        $uploadUrl .= '?orgId=' . urlencode(Config::get('sfla.organizationId'));
        $uploadUrl .= '&chatKey=' . urlencode($chatKey);
        $uploadUrl .= '&fileToken=' . urlencode($fileToken);
        $uploadUrl .= '&encoding=UTF-8';
        return $uploadUrl;
    }

    /**
     * Check if given upload URL is valid
     * @param string $url
     * @return string $url
     */
    protected function validateUploadUrl(string $url)
    {
        if (strpos($url, $this->uploadBaseUrl) === false) throw $this->error(AppEx::E_UPLOAD_URL_ERROR);
        return $url;
    }
}
