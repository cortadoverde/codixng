<?php

namespace App\Controller\Api;

use App\Controller\Controller;

use App\Model\LandingExport;

use App\Component\Prosumia\Cuitificacion;

use Illuminate\Database\Capsule\Manager AS DB;

class Prosumia extends Controller
{
  
  public function is_stored( $request, $response, $args )
  {
    $email = $request->getParam('email');
    // $email = 'p.a.samu@gmail.com';
    $componentCuitificacion = new Cuitificacion;
    return $response->withJson( ['found' => $componentCuitificacion->is_stored($email) ] );
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
  
  public function status( $request, $response, $args )
  {
    $landing_id = $args['id'];
    
    try {
      $task = LandingExport::where('landing_id', $landing_id)->firstOrFail();
    } catch (\Exception $e) {
      return $response->withJson(['status' => 'ok', 'data' => []]);
    }
    return $response->withJson(['status' => 'ok', 'data' => [$task]]);
    
    
  }
  
  public function reset( $request, $response, $args )
  {
    $landing_id = $args['id'];
    try {
      $task = LandingExport::where('landing_id', $landing_id)->firstOrFail();
      $task->delete();
      
    } catch (\Exception $e) {
      
    }
    
    $task = new LandingExport;
    $task->landing_id = $landing_id;
    $task->save();
    
    return $response->withJson(['status' => 'ok', 'data' => [$task]]);
    
    
  }
}
