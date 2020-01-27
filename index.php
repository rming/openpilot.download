<?php
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use RKA\Middleware\IpAddress;
use GeoIp2\Database\Reader;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/geoip2/geoip2.phar';

$app = AppFactory::create();


// ipaddress middleware
$checkProxyHeaders = true;
$trustedProxies = ['10.0.0.1', '10.0.0.2'];
$app->add(new IpAddress($checkProxyHeaders, $trustedProxies));

$app->get('/', function ($request, $response, $args) {
    $response->getBody()->write("hello openpilot");
    return $response;
});

$app->get('/{fork}/{branch}', function ($request, $response, $args) {
    $fork   = $args['fork'];
    $branch = $args['branch'];
    $reader = new Reader(__DIR__ . '/geoip2/mmdb/GeoLite2-City.mmdb');
    $ip     = $request->getAttribute('ip_address');
    $data   = $reader->city($ip);
    var_dump($data);
    // $body = sprintf("fork: %s<br>branch: %s", $fork, $branch);
    // $response->getBody()->write($body);
    return $response;
});

$app->run();
