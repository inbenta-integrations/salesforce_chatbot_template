<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-Inbenta-Key, X-Inbenta-Session, X-Liveagent-Sequence, X-Adapter-Session-Id");

if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
    die;
}

$publicPath = str_replace("/public", "", getcwd());
define('PUBLIC_PATH', $publicPath);
define('APP_ROOT', PUBLIC_PATH . "/src");

use App\Facade\Request;
use App\Facade\Response;
use App\Facade\Router;
use App\Model\Config;
use Utils\Logger;

require __DIR__ . '/../vendor/autoload.php';

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
$router->post('/message/file', "App\\Controllers\\Api\\MessageController@fileAction");

$router->before('OPTIONS', '.*', function () {
    http_response_code(200);
    die();
});

$router->set404(function () {
    echo "Page not found";
});

$router->run();
