<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 * Redis configuration
 */

return [
    'url' => $_ENV['REDIS_URL'] ?? '',
    'ttl' => $_ENV['REDIS_TTL'] ?? 3600
];
