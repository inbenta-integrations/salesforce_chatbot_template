<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

define('PUBLIC_PATH', $_SERVER['DOCUMENT_ROOT']);
define('APP_ROOT', PUBLIC_PATH . "/src");

use App\Facade\Request;
use App\Facade\Response;
use App\Facade\Router;
use App\Model\Config;
use Utils\Logger;

require __DIR__.'/../vendor/autoload.php';

$request = new Request();
$response = new Response();

Logger::init(Config::get('logs'));
Logger::logText('Initialize');

/**
 * Create a new router instance.
 */
$router = new Router($request, $response);

$router->post('/init', "App\Controllers\Api\InitController@initAction");
$router->get('/availability', "App\Controllers\Api\AvailabilityController@checkAction");
$router->get('/message/receive', "App\\Controllers\\Api\\MessageController@getAction");
$router->post('/message/send', "App\\Controllers\\Api\\MessageController@postAction");

$router->before('OPTIONS', '.*', function() {
    http_response_code(200);
    die();
});

$router->set404(function() {
    echo "Page not found";
});

$router->run();