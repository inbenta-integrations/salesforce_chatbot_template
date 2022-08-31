<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-Inbenta-Key, X-Inbenta-Session, X-Liveagent-Sequence, X-Adapter-Session-Id");
header('Content-Type: application/json');

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
use App\Controllers\Api\DomainController;

require __DIR__ . '/../vendor/autoload.php';

$envPath = $publicPath . '/.env';
if (is_file($envPath)) {
    Dotenv\Dotenv::createImmutable($publicPath)->safeLoad();
}

$request = new Request();
$response = new Response();

Logger::init(Config::get('logs'));
Logger::logText('Initialize');

/**
 * Create a new router instance.
 */
$router = new Router($request, $response);

$router->before('OPTIONS', '.*', function () {
    http_response_code(200);
    die();
});

$router->before('GET|POST', '.*', function () {
    // Validate origin domain
    DomainController::domainValidation();
});

$router->post('/init', "App\Controllers\Api\InitController@initAction");
$router->get('/availability', "App\Controllers\Api\AvailabilityController@checkAction");
$router->get('/message/receive', "App\\Controllers\\Api\\MessageController@getAction");
$router->post('/message/send', "App\\Controllers\\Api\\MessageController@postAction");
$router->post('/message/file', "App\\Controllers\\Api\\MessageController@fileAction");
$router->post('/createCase', "App\Controllers\Api\CaseController@createCaseAction");

$router->set404(function () {
    echo "Page not found";
});

$router->run();
