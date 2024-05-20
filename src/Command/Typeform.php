<?php
namespace App\Command;

use App\Model\Client AS Model;
use App\Model\LandingResponses;
use Illuminate\Database\Capsule\Manager AS DB;

use App\Component\Prosumia\Typeform AS TfComponent;

class Typeform extends Command
{
  use HasMenu;
  protected $signature = "typeform:menu";

  protected $description = "Componente para crear usuarios";

  private $states = [
    'reparar' => [
      'title' => 'Buscar respuestas no capturadas'
    ]
  ];
  
  private function _state_params()
  {
    
    $items = LandingResponses::whereNull('f')->where('landing_id',4)->get();
    
    foreach( $items AS $item ) {
      $user = json_decode( utf8_encode ( $item->user_data ) );
      $url_params = array();
      parse_str(parse_url($user->server->HTTP_REFERER, PHP_URL_QUERY), $url_params);
      
      $item->f = isset( $url_params['f'] ) ? $url_params['f'] : 'N/A';
      $item->i = isset( $url_params['i'] ) ? $url_params['i'] : 'N/A';
      $item->query_string = json_encode($url_params);
      $item->save();
    
      $this->info( print_r([$item->id, $url_params], true) );
    }
    
  }
  
  private function _state_check()
  {
    $token = '5NfzZJv29xKLzkpvHU3Z83GFEUXmoUnh36KjBQhfgMKD'; //$this->ask('token');
    $typeform_id = 'UAW7g9Fj';//$this->ask('typeform_id');
    $response_id = $this->ask('ultimo id');
    $typeformComponent = new \App\Component\Prosumia\Typeform($token);
    
    // Obtener la ultima respuesta capturada 
    //$response_id = 
    
    // Obtener 1000 registros 
    $data_tf = $typeformComponent->find($typeform_id, null, $response_id);
    foreach($data_tf->items AS $restf ) {
      
      $count = \App\Model\TfDarelpaso::where('response_id', $restf->token)->count();
      if( $count == 0 ) {
        $item = new \App\Model\TfDarelpaso;
        $item->response_id = $restf->token;
        $item->playload = json_encode($restf);
        $item->tf_submitted_at = str_replace('Z', '', $restf->submitted_at);
        $item->save();
        $this->info(' ok - token: ' . $restf->token );
      }else{
        $this->info(' error - token:' . $restf->token . ' (ya esta registrado)');
      }
      
    }
    
    $this->info('Total items: ' . $data_tf->total_items );
  }
  
  private function _state_sync()
  {
    
    $items = LandingResponses::where('response_json->total_items', 0)->get();
    $component = new TfComponent();
    foreach( $items AS $n => $response ) {
      $response_json = $component->get_response( 'oB5qiWJ4', $response->response_id );
      $response->response_json = json_encode($response_json);
      $response->save();
      
    }
    
  }
  
  private function _state_consultar_hash()
  {
    $hash = $this->ask('hash');
    $typeform_id = "hHg59S";
    $typeformComponent = new \App\Component\Prosumia\Typeform;

    try {
      $response = $typeformComponent->find($typeform_id, $hash);
      $this->info( print_r($response, true) );

    } catch (\Exception $e) {
      $thi->info( $e->getMessage() );
    }
    
  }
  
  private function _state_cuitificar()
  {
    $meses = [
      'enero' => '01',
      'febrero' => '02',
      'marzo' => '03',
      'abril' => '04',
      'mayo' => '05',
      'junio' => '06',
      'julio' => '07',
      'agosto' => '08',
      'septiembre' => '09',
      'octubre' => '10',
      'noviembre' => '11',
      'diciembre' => '12'
    ];
    
    $registros = DB::select("SELECT id, hash, guid, gid, cuitificado, cellphone, email  from clients WHERE  hash IN ( SELECT hash_id FROM typeform_answers ) AND process = 1 AND  puntaje IS NOT NULL  AND cellphone IS NOT NULL AND ( cuitificado IS NULL OR cuitificado = 0)");
    
    foreach( $registros AS $registro ) {
       $this->info("Procesando registro {$registro->id}");
       $respuestas = DB::select("SELECT ta.*, tf.title FROM typeform_answers ta INNER JOIN typeform_fields tf ON tf.id = ta.field_id WHERE ta.hash_id = \"{$registro->hash}\"");
       $month = null;
       $day = null;
       $year = null;
       $document_last_digits = null;
       foreach( $respuestas AS $respuesta ) {
         switch ($respuesta->field_id) {
           case 'XLgBAXtrSnhK':
           case 'uIDujQVWJiVH':
             $month = $respuesta->value;
             break;
           case 'BjQjWxMoBglC':
           case 'cRg2wkVm7ETM':
             $day = $respuesta->value;
             break;
           case 'rcNYNduAe1ed':
           case 'WfJUu0kSz2fq':
             $year = $respuesta->value;
             break;
           case 'yPB3PHATEBSS':
           case 'lBEH69AMEuHH':
             $document_last_digits = $respuesta->value;
             break;
         }
       }
       
       $guid = null;
       $cuitificado = 0;
       $this->info( print_r($respuestas, true) );
       if( isset( $meses[strtolower($month)] ) ) {
         
         $birthdate = $year . '-' . $meses[strtolower($month)] . '-' . $day;
         
         $params = [];
         
         if( $registro->email !== null ) {
           $params = [ 'email' => $registro->email ];
         }
         
         if( $registro->cellphone !== null ) {
           $params = [ 'mobile' => $registro->cellphone ];
         }
         
         
         $client = new \App\Component\Prosumia\Cuitificacion;
         $this->info('Procesando ' . $birthdate .' - ' . $document_last_digits);
         $this->info(print_r([$params, $registro], true)); 
         $cuitificacionResult = $client->cuitificar( $birthdate, $document_last_digits, $params );
         if( $cuitificacionResult !== false ) {
           if( count( $cuitificacionResult ) == 1 ) {
             $cuitificado = 1;
             $guid = $cuitificacionResult[0]->guid;
             $this->info( print_r($cuitificacionResult[0], true) );
           } else {
             $this->info( count( $cuitificacionResult ) );
           }
         } else {
           $this->info('error cuitificar');
         }
       }
       
       $this->info( print_r( [$cuitificado, $guid, $registro->id], true));
       DB::select('UPDATE clients SET cuitificado = ?, guid = ? WHERE id = ?', [$cuitificado, $guid, $registro->id]);
    }
  }
  
  private function _state_reparar()
  {
    // SELECT * FROM clients WHERE process = 1 AND hash NOT IN ( SELECT hash_id FROM typeform_answers )
    $pentientes = Model::select('id', 'typeform_id', 'hash')
                        ->where('process', 1)
                        ->whereNotIn('hash', function($query) {
                          $query->select('hash_id')
                           ->from('typeform_answers');
                        })
                        ->limit(100)
                        ->get();

    $progress = 0;

    foreach( $pentientes AS $client ) {

        $typeformComponent = new \App\Component\Prosumia\Typeform;

        try {
          $response = $typeformComponent->find($client->typeform_id, $client->hash);
          $this->info( print_r($response, true) );

        } catch (\Exception $e) {
          $thi->info( $e->getMessage() );
          break;
        }

        if( $response->total_items > 0 ) {
          $client->puntaje =  $response->items[0]->calculated->score;

          foreach($response->items[0]->answers AS $_answer){
              switch ($_answer->type) {
                case 'choice':
                $res = $_answer->choice->label;
                break;
                case 'choices':
                $res = implode($_answer->choices->labels, ',');
                break;
                case 'boolean':
                $res = intval( $_answer->boolean );
                break;
                default:
                $res = $_answer->{$_answer->type};
                break;
              }

              try {
                $this->info( $_answer->field->id . ':' . $res );
                $typeFormAnswer = \App\Model\TypeformAnswer::updateOrCreate(
                  ['form_id' => $client->typeform_id, 'field_id' => $_answer->field->id, 'hash_id' => $client->hash ],
                  ['value' => $res]
                );
                $client->process = 1;
              } catch (\Exception $e) {
                $this->info( print_r( $e->getMessage(), true) );
              }
          }
        } else {
          $client->process = 0;
          $client->puntaje = NULL;
        }


        $client->save();

    }

    $header = ['id', 'form_id', 'hash', 'puntaje', 'process'];
    $this->table( $header, $pentientes->toArray() );
  }

}
