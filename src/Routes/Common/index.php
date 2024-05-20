<?php
use Illuminate\Database\Capsule\Manager AS DB;

$app->get('/', function($req, $res) use ($app) {
  return $res->withRedirect( '/admin' );
});

$app->get('/ui/', \App\Controller\Utils\Ui::class.":components")->setName('utils.ui.components');
$app->post('/typeform/process/{landing_id}/', \App\Controller\Api\Typeform::class.":process")->setName('api.typeform.process');
