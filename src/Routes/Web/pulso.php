<?php

$app->get('/', function($req, $res) use ($app) {
  return $res->withRedirect('http://pulso.social', 301);
});

// $app->get('/', \App\Controller\Utils\Ui::class.":components")->setName('utils.ui.components');
$app->get('/samu/', \App\Controller\Utils\Ui::class.":fb")->setName('utils.ui.fb');
$app->get('/google/', \App\Controller\Utils\Ui::class.":google")->setName('utils.ui.google');
$app->post('/google/', \App\Controller\Utils\Ui::class.":google")->setName('utils.ui.google');
$app->get('/{hash}/', \App\Controller\Pulso\Encuestas::class.":comenzar")->setName('pulso.encuestas.comenzar');
$app->get('/error/404/', \App\Controller\Pulso\Encuestas::class.":page_error")->setName('pulso.encuestas.page_error');
$app->post('/typeform/process/{landing_id}/', \App\Controller\Api\Typeform::class.":process")->setName('api.typeform.process');
$app->post('/typeform/login/{landing_id}/', \App\Controller\Api\Typeform::class.":login")->setName('api.typeform.login');

$app->group('/api/v1', function($app){

    $app->get('/is_stored/', \App\Controller\Api\Prosumia::class)->setName('api.prosumia.is_stored');

});