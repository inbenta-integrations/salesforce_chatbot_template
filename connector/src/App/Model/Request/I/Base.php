<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Request\I;

interface Base
{
    const POST = 'POST';
    const GET = 'GET';

    /**
     * @param string $url
     * @param mixed $request
     * @param array $params
     * @param array $headers
     * @return $this
     */
    public function send($url, $request = null, array $params = [], array $headers = []);

    /**
     * @return Response
     */
    public function response();
}
