<?php

// Login y Logout
$app->get('/login/', App\Controller\Auth::class.":login")->setName('login');

$app->post('/login/', App\Controller\Auth::class.":check_auth")->setName('auth.post.login');

$app->get('/logout/', App\Controller\Auth::class.":logout")->setName('logout');


$app->group('/admin', function($app){

    $app->get('/', \App\Controller\Admin\Dashboard::class)->setName('admin.dashboard.index');
    
    $app->get('/encuestas/crear/', \App\Controller\Admin\Encuestas::class.":crear")->setName('admin.encuestas.crear');
    $app->get('/encuestas/ver/{id}/', \App\Controller\Admin\Encuestas::class.":view")->setName('admin.encuestas.view');
    $app->get('/encuestas/editar/{id}/', \App\Controller\Admin\Encuestas::class.":editar")->setName('admin.encuestas.editar');
    $app->post('/encuestas/editar/{id}/', \App\Controller\Admin\Encuestas::class.":save")->setName('admin.encuestas.save');
    $app->post('/encuestas/crear/', \App\Controller\Admin\Encuestas::class.":add")->setName('admin.encuestas.add');
    $app->get('/encuestas/preview/{hash}/', \App\Controller\Admin\Encuestas::class.":preview")->setName('admin.encuestas.preview');
    $app->get('/encuestas/exportar/{id}/', \App\Controller\Admin\Encuestas::class.":exportar")->setName('admin.encuestas.exportar');
    
    $app->group('/images', function($app){
      $app->get('/', function($req,$res){
        return $res->withRedirect( '/admin' );
      });
      $app->post('/upload/', \App\Controller\Admin\Images::class.":upload")->setName('admin.image.upload');
    });
    
})->add(new App\Middleware\Auth( $app->getContainer() ));


$app->group('/api/v1', function($app){

    $app->get('/stats/', \App\Controller\Api\Stats::class)->setName('api.stats.dashboard');
    $app->get('/chart/', \App\Controller\Api\Stats::class)->setName('api.stats.charts');
    $app->get('/is_stored/', \App\Controller\Api\Prosumia::class)->setName('api.prosumia.is_stored');
    $app->get('/export/status/{id}/', \App\Controller\Api\Prosumia::class)->setName('api.prosumia.status');
    $app->get('/export/create/{id}/', \App\Controller\Api\Prosumia::class)->setName('api.prosumia.create');
    $app->get('/export/reset/{id}/', \App\Controller\Api\Prosumia::class)->setName('api.prosumia.reset');

})->add(new App\Middleware\Auth( $app->getContainer() ));
