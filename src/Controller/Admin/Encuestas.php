<?php

namespace App\Controller\Admin;

use App\Model\Landing;
use App\Model\LandingResponses;
use App\Model\LandingStats;
use App\Model\UserStats;

use App\Model\TemplateConfig;
use App\Component\Prosumia\Typeform;
use Slim\Http\Stream;

use Illuminate\Database\Capsule\Manager AS DB;

class Encuestas extends \App\Controller\Controller
{
    public function view( $request, $response, $args ) 
    {
      
      // Verificar si existe la encuesta y si pertenece al usuarios 
      if( ! isset($args['id'] ) ) return $response->withRedirect( $this->router->pathFor('admin.dashboard.index') );
      try {
        $landing = Landing::findOrFail($args['id']);
        if( $landing->user_id !== $_SESSION['user']['id'] )
          return $response->withRedirect( $this->router->pathFor('admin.dashboard.index') );
        
        // Obtenemos las estadisticas para la landing
        $landing_stats = LandingStats::where('landing_id', $landing->id)->first();
        
        // Obtenemos los datos del reporte 
        
        $this->view->set('landing', $landing);
        $this->view->set('landing_stats', $landing_stats);
        
        return $this->render( $response, 'admin/encuestas/ver' );
        
      } catch (\Exception $e) {
        return $response->withRedirect( $this->router->pathFor('admin.dashboard.index') );
      }
      
    }

    public function crear($request, $response)
    {
      return $this->render( $response, 'admin/encuestas/crear' );
    }

    public function add($request, $response)
    {
      
      //grabar los registros
      $data = $request->getParams();

      $typeform_host = 'smix.typeform.com';
    // si el campo typeform_id es una url, se obtiene el id del de la url 
    if( filter_var($data['typeform_id'], FILTER_VALIDATE_URL) ) {
      $typeform_host = parse_url($data['typeform_id'], PHP_URL_HOST);
      $id =  explode('/', $data['typeform_id']) ;
      $typeform_id   = array_pop($id);
      // obtener el host de la url
    }

    $tokens = [
      'smix.typeform.com' => env("token.smix"),
      'form.typeform.com' => env("token.form")
    ];

      $component = new Typeform($tokens[ $typeform_host ]);
      $typeform_data = $component->get_answers( $typeform_id );
      
      // Creamos la landing para ejecutar las encuestas 
      $landing = new Landing;
      $landing->user_id = $_SESSION['user']['id'];
      $landing->description = $data['description'];
      $landing->title = $data['title'];
      $landing->hash = uniqid();
      $landing->template_id = 1;
      $landing->typeform_id = $data['typeform_id'];
      $landing->slug = $data['slug'];
      $landing->report_url = $data['report_url'];
      $landing->tf_fields = json_encode( $typeform_data->fields );
      $landing->save();
      
      // Guardar las estadisticas al crear 
      $landing_stats = new LandingStats();
      $landing_stats->landing_id = $landing->id;
      $landing_stats->count_answers = count($typeform_data->fields);
      $landing_stats->total_completed = 0;
      $landing_stats->total_cuitificados = 0;
      $landing_stats->save();
      
      // Guardar las estadisticas para el usuario
      $user_stats = UserStats::findOrCreateByUser($_SESSION['user']['id']);
      $user_stats->total_landing = $user_stats->total_landing + 1;
      $user_stats->save();
      
      return $response->withRedirect( $this->router->pathFor('admin.encuestas.view', [ 'id' => $landing->id ]) );
    }
    
    public function editar($request, $response, $args)
    {
      $landing = Landing::with('template_config')->findOrFail($args['id']);
      
      $data = $request->getParams();
      
      $this->view->set('item', $landing);
      
      return $this->render( $response, 'admin/encuestas/crear' );
    }
    
    public function save($request, $response, $args)
    {
      $landing = Landing::findOrFail($args['id']);
      
      $data = $request->getParams();
      
      $component = new Typeform();
      $typeform_data = $component->get_answers( $data['typeform_id'] );
      
      $landing->typeform_id = $data['typeform_id'];
      $landing->template_id = $data['template_id'];
      $landing->title = $data['title'];
      $landing->description = $data['description'];
      $landing->slug = $data['slug'];
      $landing->report_url = $data['report_url'];
      $landing->tf_fields = json_encode( $typeform_data->fields );
      $landing->config = $data['config'];
      $landing->save();
      // Buscar configuraciones 
      
      // Actualizar stats de typeform 
      $landing_stats = LandingStats::where('landing_id', $landing->id)->first();
      $landing_stats->count_answers = count($typeform_data->fields);
      $landing_stats->save();
      
      return $response->withRedirect( $this->router->pathFor('admin.encuestas.view', ['id' => $args['id']]) );
    }
    
    public function push($request, $response, $args)
    {
      //grabar los registros
      $data = $request->getParams();
      echo '<pre>';
      print_r($data);
      die;
      return $this->render( $response, 'admin/encuestas/crear' );
    }
    
    public function preview( $request, $response, $args )
    {
      $model = Landing::find($args['hash']);
      $this->layout = 'landing/layout_' . $model->template_id;
      
      $this->view->set('item', $model);
      return $this->render( $response, 'landing/preview' );
    }

    public function exportar( $request, $response, $args )
    {
      
      $landing = Landing::findOrFail($args['id']);
      
      // obtener metadata del form 
      $fields = json_decode( utf8_encode($landing->tf_fields) );
      
      $headers = [
        'id',
        'user_fb',
        'fecha',
        'i',
        't'
      ];
      
      $n = count($headers);
      foreach( $fields AS $field ) {
        $_title[$n] = $field->id;
        $headers[$n] = $field->title;
        $n++;
      }
      
      
      // Buffer para crear el csv y que no se clave con el 
      // metodo tradicional para archivos pesados
      $stream = fopen('php://memory', 'w+');
      // UTF-8 BOOm
      fputs( $stream, "\xEF\xBB\xBF" );
      // Headers 
      fputcsv($stream, array_map('utf8_decode',array_values($headers)), ';');  
      
      $responses = LandingResponses::where('landing_id', $args['id'])->get();
      $rows = [];
    
      foreach( $responses AS $_response ) {
        
        $user_data     = json_decode( utf8_encode( $_response->user_data ) ) ;
        $response_json = json_decode( utf8_encode( $_response->response_json ) ) ;
        if( isset( $response_json->items[0]->answers ) ) {
          $row = [
            $_response->id,
            $user_data->fb->userID,
            $_response->created_at->format('Y-m-d'),
            $_response->i == 'N/A' ? '' : $_response->i,
            $_response->f == 'N/A' ? '' : $_response->f
          ];
          foreach( $response_json->items[0]->answers AS $answer ) {
            $index = array_search($answer->field->id, $_title);
            
            switch ($answer->type) {
              case 'choice':
              $res = $answer->choice->label;
              break;
              case 'choices':
              $res = implode($answer->choices->labels, ',');
              break;
              case 'boolean':
              $res = intval( $answer->boolean );
              break;
              default:
              $res = $answer->{$answer->type};
              break;
            }
            
            $row[$index] = $res;
          }
        }
        fputcsv($stream, array_map('utf8_decode',array_values($row)), ';');
      
      }
      
      
      rewind($stream);
      
      $filename = time(). '_export.csv';
      $response = $response->withHeader('Content-Type', 'text/csv;charset=utf-8');
      $response = $response->withHeader('Content-Disposition', 'attachment; filename="'. $filename.'"');
      
      return $response->withBody(new \Slim\Http\Stream($stream));
    
    }
    
    
    public function exportar_post( $request, $response, $args )
    {
      //$this->test_charset();
      $filename = "data_";
      
      $params = $request->getParams();
      
      $range  = explode('-', $params['range']);
      $start_date = date('Y-m-d 00:00:00', strtotime( trim( $range[0] ) ) );
      $end_date = date('Y-m-d 23:59:59', strtotime( trim( $range[1] ) ) );
      
      $filename .= date('Y-m-d', strtotime( trim( $range[0] ) ) ) . "_" . date('Y-m-d', strtotime( trim( $range[1] ) ) );
      
      $data = \App\Model\Client::select([
        'date AS Fecha', 
        'clients.typeform_id AS Typeform', 
        'hash AS Hash', 
        'cuitificado AS Cuitificado', 
        'mid AS MID', 
        'gid AS GID', 
        'guid AS GUID',
        'browser_lat AS Latitud',
        'browser_lng AS Longitud',
        'geoplugin_city AS Ciudad IP',
        'geoplugin_region AS Provincia IP',
        'geoplugin_countryName AS Pais IP',
        'puntaje AS Score'
      ])
      ->where('process',1)->whereNotNull('puntaje')->whereBetween('date', [$start_date, $end_date]);
      
      $map = ['Genero', 'Fecha de Cumpleaños', 'Ultimos 3 DNI', 'Salud', 'Viaje', 'Contacto', 'ContactoQuien', 'ContactoFrequencia', 'Fiebre', 'CuantaFiebre','Sintomas', 'Situacion', 'Necesidad'];
  
      if( $params['typeform'] !== '' ) {
        $filename .= "_{$params['typeform']}";
        $data = $data->GroupName( $params['typeform'] );
      } else {
        $filename .= "_Completo";
      }
      
      $clients =  $data->get();
      
      $filename .= ".csv";
      
      if( $clients->count() > 0 ) {
        $clients =$clients->toArray();
        $item  = $clients[0];
        
        $headers = array_map( function( $string ) {
          return preg_replace("/[\r\n|\n|\r]+/", "", $string);
        }, array_keys( $item ) );
        $headers = array_merge( $headers, $map);

        // Buffer para crear el csv y que no se clave con el 
        // metodo tradicional para archivos pesados
        $stream = fopen('php://memory', 'w+');
        // UTF-8 BOOm
        fputs( $stream, "\xEF\xBB\xBF" );
        // Headers 
        fputcsv($stream, $headers, ';');
        
      
        
        
        
        foreach ($clients as $n => $client ) {
          $month = null;
          $day = null;
          $year = null;
          
          $answersArray = [null, null, null, null, null, null, null, null, null, null, null, null, null];
          $answers = DB::select("
              SELECT tf.title, ta.field_id, ta.value 
              FROM typeform_answers ta 
              INNER JOIN typeform_fields tf
              ON ta.field_id = tf.id
              WHERE form_id = ? AND hash_id = ?
          ", [$client['Typeform'], $client['Hash']]);
          
          foreach( $answers AS $respuesta ) {
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
                $answersArray[2] = $respuesta->value;
                break;
              case 'eFjoFFGiEfu7':
              case 'GHEyHc8sZdo4':
                $answersArray[0] = $respuesta->value;
                break;
              case 'OLUybKNL29as':
              case 'LLm1Tcw6Zxrk':
                $answersArray[3] = $respuesta->value;
                break;
              case 'fhms51JhC9wr':
              case 'lAHRlqD053AW':
                $answersArray[4] = $respuesta->value;
                break;
              case 'v1f4tzxW75iK':
              case 'VZho3XoVnP2l':
                $answersArray[5] = $respuesta->value;
                break;
              case 'AGLO5AAF4Xz9':
              case 't3V3CTt2GDSz':
                $answersArray[6] = $respuesta->value;
                break;
              case 'ge36iez7Fcq1':
              case 'ILlYdP7BXVlr':
                $answersArray[7] = $respuesta->value;
                break;
              case 'AZM3C3CkEwsk':
              case 'kwESBkEZOBOT':
                $answersArray[8] = $respuesta->value;
                break;
              case 'AJyNxfCW2tU4':
              case 'OBJ9or5BsSTP':
                $answersArray[9] = $respuesta->value;
                break;
              case 'zdg1TupCXhsW':
                $answersArray[10] = $respuesta->value;
                break;
              case 'MT3yNIwcYLCc':
                  $answersArray[11] = $respuesta->value;
                  break;
              case 'SU11RBPcUgVV':
              case 'jry10p5yBqs2':
                  $answersArray[12] = $respuesta->value;
                  break;
            }
          }
        
          if( $month != '' ) {
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
            
            $answersArray[1] = "{$year}-{$meses[strtolower($month)]}-{$day}";
          }
        
          foreach($answersArray AS $_an => $value ) {
            $client[$map[$_an]] = $value;
          }
          
          fputcsv($stream, array_map('utf8_decode',array_values($client)), ';');
        }
        
        rewind($stream);

        $response = $response->withHeader('Content-Type', 'text/csv;charset=utf-8');
        $response = $response->withHeader('Content-Disposition', 'attachment; filename="'. $filename.'"');

        return $response->withBody(new \Slim\Http\Stream($stream));
    
      } else {
        $_SESSION['msg'] = "No se encontraron resultados para el rango utilizado";
        return $response->withRedirect( $this->router->pathFor('admin.encuestas.exportar') );
      }
    }
}
