<?php

namespace App\Controller\Api;

use App\Controller\Controller;

use App\Model\Client;

use Illuminate\Database\Capsule\Manager AS DB;

class Stats extends Controller
{
  
  public function dashboard( $request, $response, $args )
  {

    $model = Client::where('process',1);

    if( $request->getQueryParam('group') )
      $model->GroupName($request->getQueryParam('group'));

    $response_data = [
      'total' => $model->count(),
      'week'  => $model->Week()->count(),
      'today'  => $model->Today()->count()
    ];

    return $response->withJson($response_data);

  }

  public function charts( $request, $response, $args )
  {
    $model = Client::where('process', 1);
    
    if( $request->getQueryParam('country') ) {
      if( $request->getQueryParam('country') != '' )
        $model->Pais($request->getQueryParam('country'));
    }
    
    $data = $model->select(DB::raw('count(*) as count, DATE(date) as day'))
          ->groupBy('day')
          ->get();
    $labels = $data->pluck('day');
    $values = $data->pluck('count');
    
    return $response->withJson([ 'labels' => $labels, 'data' => $values ]);
          
  }
}
