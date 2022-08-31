<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Controllers\Api;

use Exception;

class DomainController extends Controller
{

    /**
     * Validate if domain is in white list
     */
    public static function domainValidation(): void
    {
        $headers = getallheaders();

        $origin = isset($headers['Origin']) ? $headers['Origin'] : '';
        $origin = $origin === '' && isset($headers['Host']) ? $headers['Host'] : $origin;
        $origin = str_replace(['https://', 'http://'], '', $origin);
        try {
            if (is_null($origin) || $origin === '') {
                throw new Exception("Domain error");
            } elseif (isset($_ENV['DOMAINS']) && $_ENV['DOMAINS'] !== '') {
                $domains = explode(",", str_replace(" ", "", $_ENV['DOMAINS']));
                if (!in_array($origin, $domains)) {
                    throw new Exception("Domain error");
                }
            }
        } catch (Exception $e) {
            http_response_code(404);
            die;
        }
    }
}
