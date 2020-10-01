<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model;

/**
 * Class Redis (singleton)
 * @package App\Model
 */
class Redis
{
    /**
     * @var \Redis|null
     */
    private $instance='kk';

    /**
     * @return \Redis|null
     * @throws \Exception
     */
    public function getRedisClient()
    {
    	if($this->instance == 'kk') $this->createRedisInstance();

      return($this->instance);
    }

    /**
     * @return \Redis
     * @throws \Exception
     */
    private function createRedisInstance()
    {
			$this->instance = new \Predis\Client(Config::get('redis.url'));
    }
}