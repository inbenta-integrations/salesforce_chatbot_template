<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Model\Request;

class Curl implements I\Base
{
    /**
     * @var I\Response
     */
    protected $response = null;

    /**
     * Default params
     * @var array
     */
    protected $params = [
        'method'          => 'POST',
        'timeout'         => 200,
        'connect-timeout' => 100,
    ];

    protected $headers = [
        'Content-type: application/json; charset=UTF-8',
    ];

    public function __construct(I\Response $response)
    {
        $this->response = $response;
    }

    /**
     * @param string $url
     * @param mixed $request Request params or data
     * @param array $params Connection params
     * @param array $headers
     * @return $this
     * @throws \Exception
     */
    public function send($url, $request = null, array $params = [], array $headers = [])
    {
        $responseHeaders = [];
        $headerFunc = function ($curl, $header) use (&$responseHeaders) {
            $len = strlen($header);

            $keyVal = explode(': ', $header, 2);

            if (count($keyVal) < 2)
                return $len;

            list($key, $val) = $keyVal;

            $responseHeaders[strtolower(trim($key))] = trim($val);

            return $len;
        };
        $params = array_merge($this->params, $params);

        $ch = curl_init();
        try {
            curl_setopt_array($ch, $this->getOptions($url, $request, $params));

            if (count($headers)) {
                $preparedHeaders = [];
                foreach ($headers as $key => $requestHeader) {
                    $preparedHeaders[] = "$key: $requestHeader";
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($this->headers, $preparedHeaders));
            }

            curl_setopt($ch, CURLOPT_HEADERFUNCTION, $headerFunc);

            $response = curl_exec($ch);
            if (curl_errno($ch) != 0)
                throw new \Exception(curl_error($ch), curl_errno($ch));

            $info = curl_getinfo($ch);

            $this->response
                ->setCode($info['http_code'])
                ->setHeaders(new \App\Model\Varien\DataObject($responseHeaders))
                ->setBody(substr($response, $info['header_size']));
        } catch (\Exception $ex) {
            throw $ex;

        } finally {
            curl_close($ch);
        }

        return $this;
    }

    /**
     * @return I\Response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Implement curl options
     * @param $url
     * @param $request
     * @param $params
     * @return array
     */
    private function getOptions($url, $request, $params)
    {
        $options = [
            CURLOPT_VERBOSE        => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_ENCODING       => "",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            //            CURLOPT_COOKIE         => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_CONNECTTIMEOUT => (int)$params['connect-timeout'],
            CURLOPT_TIMEOUT        => (int)$params['timeout'],
        ];

        switch ($params['method']) {
            case static::POST:
                $options[CURLOPT_POST] = true;
                    $options[CURLOPT_POSTFIELDS] = $request ?: '{}';
                break;
            default:
                if (!empty($request))
                    $url .= '?' . http_build_query($request);
        }
        $options[CURLOPT_URL] = $url;

        return $options;
    }
}