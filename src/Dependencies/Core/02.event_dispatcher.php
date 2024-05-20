<?php 

$container = \App\Loader::getContainer();

$container['dispatcher'] = function( $c ) {
    return new \Illuminate\Events\Dispatcher();
};
