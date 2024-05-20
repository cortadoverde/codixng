<?php

ini_set('display_errors', false);
ini_set('error_reporting', 0);

! defined('DS')      ? define( 'DS', DIRECTORY_SEPARATOR ) : null ;
! defined('ROOT')    ? define( 'ROOT',  dirname(__DIR__) ) : null ;
! defined('APP_DIR') ? define( 'APP_DIR', ROOT . DS . 'src' ) : null ;
! defined('PUBLIC_DIR') ? define( 'PUBLIC_DIR', __DIR__ ) : null ;
require APP_DIR . DS .  'bootstrap.php';

$app->run();
