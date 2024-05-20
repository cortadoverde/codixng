<?php

namespace App\Controller\Api;

use App\Controller\Controller;

use App\Model\Landing;
use App\Model\LandingStats;
use App\Model\LandingLogin;
use App\Model\UserStats;
use App\Model\LandingResponses;

use App\Component\Prosumia\Typeform AS TfComponent;

use Illuminate\Database\Capsule\Manager AS DB;

class Typeform extends Controller
{
  
  public function process( $request, $response, $args )
  {
    
    $landing_id = $args['landing_id'];
    
    $landing = Landing::find($landing_id);
  
    $data = $request->getParams();
    
    // Chequear si la respuesta no existe para evitar duplicados! 
    $_check_response_exist = LandingResponses::where('response_id', $data['response_id'] )->count();
    if( $_check_response_exist > 0 ) {
      return $response->withJson(['status' => 'error', 'msj' => 'el registro ya existe']);  
    }
    
    // Respuestas de Typeform

    $landing->typeform_host = 'smix.typeform.com';
    // si el campo typeform_id es una url, se obtiene el id del de la url 
    if( filter_var($landing->typeform_id, FILTER_VALIDATE_URL) ) {
      $landing->typeform_host = parse_url($landing->typeform_id, PHP_URL_HOST);
      $id =  explode('/', $landing->typeform_id) ;
      $landing->typeform_id   = array_pop($id);
      // obtener el host de la url
    }

    $tokens = [
      'smix.typeform.com' => env("token.smix"),
      'form.typeform.com' => env("token.form")
    ];

    $component = new TfComponent( $tokens[ $landing->typeform_host ]);
    $response_json = $component->get_response( $landing->typeform_id, $data['response_id'] );

    
    // Datos de usuarios 
    $user_data = array(
      'server' => $_SERVER,
      'fb'  => $data['fb'],
      'update' => 0,
      'data' => (isset( $data['fb']['accessToken'] ) ? $this->FbRequest( $data['fb']['accessToken'] ) : [] )
    );
    
    if( isset( $data['fb']['internal_login'] ) ) {
      try {
        $landing_login = LandingLogin::find($data['fb']['internal_login']);
        $landing_login->completed = 1;
        $landing_login->save();
      } catch (\Exception $e) {
      }
    }
    
    
    $landing_responses = new LandingResponses();
    $landing_responses->landing_id = $landing->id;
    $landing_responses->response_id = $data['response_id'];
    $landing_responses->response_json = json_encode($response_json);
    $landing_responses->user_data = json_encode($user_data);
    $landing_responses->save();
    
    // Actualizar stats 
    // Stats de la landing
    $landing_stats = LandingStats::where('landing_id', $landing->id)->first();
    $landing_stats->total_completed = $landing_stats->total_completed + 1;
    $landing_stats->save();
    
    // Stats para el usuario autor de la landing 
    $user_stats = UserStats::where('user_id', $landing->user_id)->first();
    $user_stats->total_landing_completed = $user_stats->total_landing_completed + 1;
    $user_stats->save();
  
    return $response->withJson($response_json);

  }
  
  private function FbRequest( $token )
  {
    try {
      $ch = curl_init();
      
      curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/v8.0/me?field=email&access_token='.$token);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      
      $result = curl_exec($ch);
      
      if (curl_errno($ch)) {
        return null;
      }
      
      curl_close($ch);
      
      return json_decode($result);
      
    } catch (\Exception $e) {
      return ['error' => 'curl'];
    }
    
  }

  public function login( $request, $response, $args ) 
  {
    $landing_id = $args['landing_id'];
    
    $data = $request->getParams();
    
    $user_data = array(
      'server' => $_SERVER,
      'fb'  => $data['fb']
    );
    
    $login = new LandingLogin;
    $login->landing_id = $landing_id;
    $login->user_data = json_encode( $user_data );
    $login->fb_user_id =( isset( $data['fb'] ) && isset( $data['fb']['userID'] ) ) ? $data['fb']['userID'] : null ;
    $login->completed = 0;
    $login->save();
    
    return $response->withJson($login->id);
    
    
  }
}
