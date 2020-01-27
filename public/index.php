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
    $config = [
        'openpilot'=>[
            'default' =>'https://github.com/commaai/openpilot',
            'cn'      =>'https://gitee.com/afaaa/openpilot',
        ],
        'afa'=>[
            'default' =>'https://github.com/rming/openpilot',
            'cn'      =>'https://gitee.com/afaaa/openpilot-cn',
        ],
        'dragonpilot'=>[
            'default' =>'https://github.com/dragonpilot-community/dragonpilot',
            'cn'      =>'https://gitee.com/afaaa/dragonpilot',
        ],
        'kegman'=>[
            'default' =>'https://github.com/kegman/openpilot',
            'cn'      =>'https://gitee.com/afaaa/kegman',
        ],
        'gernby'=>[
            'default' =>'https://github.com/gernby/openpilot',
            'cn'      =>'https://gitee.com/afaaa/gernby',
        ],
        'arne'=>[
            'default' =>'https://github.com/arne182/openpilot',
            'cn'      =>'https://gitee.com/afaaa/arne182',
        ],
    ];
    $alias = [
        'dp'      =>'dragonpilot',
        'op'      =>'openpilot',
        'arne182' =>'arne'
    ];
    $reader = new Reader(__DIR__ . '/../geoip2/mmdb/GeoLite2-City.mmdb');
    $params = $request->getQueryParams();
    $ip     = $params['ip'] ?? $request->getAttribute('ip_address');
    try {
        $data     = $reader->city($ip);
        $country  = strtolower($data->country->isoCode);
        $timezone = $data->location->timeZone;
    } catch (Exception $e) {
        $country  = 'default';
        $timezone = 'Africa/Lome';
    }

    $forkName   = $args['fork'];
    $branchName = $args['branch'];
    $forkName   = $alias[$forkName] ?? $forkName;
    $forkConfig = $config[$forkName];
    if (!$forkConfig) {
        return $response->withStatus(404);
    }

    $forkUrl = $forkConfig[$country] ?? $forkConfig['default'];
    $vars    = [
        '{branch_name}' =>$branchName,
        '{fork_url}'    =>$forkUrl,
        '{time_zone}'   =>$timezone,
    ];

    $installer = file_get_contents(__DIR__ . '/../scripts/installer.py');
    $installer = str_replace(array_keys($vars), array_values($vars), $installer);

    $response->getBody()->write($installer);
    return $response->withHeader('Content-Type', 'application/octet-stream');
});

$app->run();
