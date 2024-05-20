<?php
session_start();
! defined('DS')      ? define( 'DS', DIRECTORY_SEPARATOR ) : null ;
! defined('ROOT')    ? define( 'ROOT', dirname( __DIR__ ) ) : null ;
! defined('APP_DIR') ? define( 'APP_DIR', ROOT . DS . 'src' ) : null;

$autoload = require_once ROOT . DS . 'vendor' . DS . 'autoload.php';

// date_default_timezone_set('UTC');

$config   = [
  "settings" => [
    "displayErrorDetails" => true
  ]
];

$app = new \Slim\App( $config );

\App\Loader::setApp( $app );

$container = \App\Loader::getContainer();

$container['composer'] = $autoload;


$routes_domain = [
  '_default_' => [
    'common' => 'Routes/Common',
    'admin'  => 'Routes/Admin'
  ],
  'local.web.pulso' => [
    'common' => 'Routes/Web'
  ],
  'web.pulso.social' => [
    'common' => 'Routes/Web'
  ],
  'devpulso.tuopinion.me' => [
    'common' => 'Routes/Dev'
  ],
  'tuopinion.me' => [
    'common' => 'Routes/Web'
  ],
  'miopinion.me' => [
    'common' => 'Routes/Dev'
  ],
];

$http_host        = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '_default_';
$routes = isset($routes_domain[$http_host]) ? $routes_domain[$http_host] : $routes_domain['_default_']; 

$container['hostname'] = $http_host;


\App\Loader::setPaths([
  'Initial' => [
    'debug' => 'Dependencies/Init'
  ],
  'Config' => [
    'core' => [
      'dir' => [
        'Config/Site',
        'Config/Database'
      ]
    ]
  ],
  'Dependencies' => [
    'core' => 'Dependencies/Core'
  ],
  'Routes' => $routes,
  'Middlewares' => [
    'app' => 'Middleware/src'
  ]
]);

\App\Loader::load();