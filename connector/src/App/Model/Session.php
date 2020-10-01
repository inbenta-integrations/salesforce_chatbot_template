<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model;

use App\Model\Varien\DataObject;

/**
 * Class Session data
 * @package App\Model
 */
class Session extends DataObject
{
    /**
     * @var string Inbenta session identifier
     */
    private static $sessionId;

    /**
     * Start session with provided key
     * @param $key
     * @throws \Exception
     */
    public static function setSessionId($key)
    {
        self::$sessionId = self::normalizeSessionId($key);
        $redis = new Redis();
        $redis = $redis->getRedisClient();
        $redis->expire(self::$sessionId, Config::get('redis.ttl'));
    }

    /**
     * @param string | null $key
     * @return mixed | null
     * @throws \Exception
     */
    public static function get($key = null)
    {
        $data = self::getSessionData();

        if ($data instanceof DataObject)
            return $data->getData($key);
        else
            return null;
    }

    /**
     * Set data to session
     * @param string | array $sessionData
     * @param $value
     * @throws \Exception
     */
    public static function setSession($sessionData, $value = null)
    {
        $redis = new Redis();
        $redis = $redis->getRedisClient();
        $data = self::getSessionData();
        if (!$data instanceof DataObject) {
            $data = new DataObject();
        }
        if (is_array($sessionData)) {
            foreach ($sessionData as $key => $value) {
                $data->setData($key, $value);
            }
        } else
            $data->setData($sessionData, $value);

        $redis->setex(self::$sessionId, Config::get('redis.ttl'), serialize($data));
    }

    /**
     * Start php session
     * @throws \Exception
     */
    public static function startSession()
    {
        try {
            session_start();

        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Destroy current session
     * @throws \Exception
     */
    public static function destroy()
    {
        try {
            if(session_id())
                session_destroy();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Change disabled symbols to character "-"
     * There are only allowed characters: -,a-zA-Z0-9
     * @param string $sessionId
     * @return string
     */
    private static function normalizeSessionId(string $sessionId)
    {
        return (string)preg_replace('/([^-,a-zA-Z0-9])/', '-', $sessionId);
    }

    /**
     * @return bool|string
     * @throws \Exception
     */
    private static function getSessionData()
    {
        $redis = new Redis();
        $redis = $redis->getRedisClient();
        $sData = $redis->get(self::$sessionId);
        $sData = unserialize($sData);
        return $sData;
    }

    /**
     * @return string
     */
    public static function getSessionId()
    {
        return self::$sessionId;
    }
}