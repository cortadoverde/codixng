<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

$container = \App\Loader::getContainer();
 
if( isset( $container['autoload']['database'] ) ) {
    $capsule = new \Illuminate\Database\Capsule\Manager;

    foreach( $container['autoload']['database'] AS $name => $config ){
        $capsule->addConnection($config, $name);
    }

    $capsule->setEventDispatcher( $container->dispatcher );

    $capsule->setAsGlobal();

    $capsule->bootEloquent();

    $container['db'] = function ($c) use ( $capsule ){
        return $capsule;
    };
}

