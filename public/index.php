<?php
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use RKA\Middleware\IpAddress;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Slim\Middleware\ContentLengthMiddleware;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../geoip2/geoip2.phar';

$app = AppFactory::create();


// ipaddress middleware
$checkProxyHeaders = true;
$trustedProxies = ['10.0.0.1', '10.0.0.2'];
$app->add(new IpAddress($checkProxyHeaders, $trustedProxies));
// Content-length
$contentLengthMiddleware = new ContentLengthMiddleware();
$app->add($contentLengthMiddleware);


$app->get('/', function ($request, $response, $args) {
    $response->getBody()->write("hello openpilot");
    return $response;
});

$app->any('/{fork}/{branch}', function ($request, $response, $args) {
    $forks = [
        'commaai','afa','dragonpilot','kegman','gernby','arne',
    ];
    $alias = [
        'dp'        =>'dragonpilot',
        'op'        =>'commaai',
        'openpilot' =>'commaai',
        'arne182'   =>'arne',
    ];
    $reader = new Reader(__DIR__ . '/../geoip2/mmdb/GeoLite2-City.mmdb');
    $params = $request->getQueryParams();
    $ip     = $params['ip'] ?? $request->getAttribute('ip_address');
    try {
        $data     = $reader->city($ip);
        $country  = strtolower($data->country->isoCode);
    } catch (Exception $e) {
        $country  = 'default';
    }

    $forkName   = strtolower($args['fork']);
    $branchName = strtolower($args['branch']);
    $forkName   = $alias[$forkName] ?? $forkName;
    $inForks    = in_array($forkName, $forks);
    if (!$inForks) {
        return $response->withStatus(404);
    }

    $country  = $country === 'cn' ? 'cn' : 'default';
    $fileName = sprintf("installer_%s_%s_%s",$forkName, $branchName, $country);

    $file = sprintf('%s/../../installers/%s', __DIR__, $fileName);
    if (!file_exists($file)) {
        return $response->withStatus(404);
    }

    $response->getBody()->write(file_get_contents($file));
    return $response->withHeader('Content-Type', 'application/octet-stream');
});

$app->run();
