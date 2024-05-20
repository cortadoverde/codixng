<?php

namespace App\Controller\Pulso;

use App\Model\Landing;
use App\Model\LandingStats;
use App\Model\UserStats;

use App\Model\TemplateConfig;
use App\Component\Prosumia\Typeform;
use Slim\Http\Stream;

use Illuminate\Database\Capsule\Manager AS DB;

class Encuestas extends \App\Controller\Controller
{
    
    public function comenzar( $request, $response, $args )
    {
      $default_login_type = 'facebook';
      
      $params = $request->getParams();
      
      $extra_params = '';
      
      $login_type = $default_login_type;
      
      if( !empty($params) ) {
        $paramsJoined = array();
        
        foreach($params as $param => $value) {
          $paramsJoined[] = "$param=$value";
        }
        
        $extra_params = implode('&', $paramsJoined);

      }
      
      $this->view->set('extra_params', $extra_params);

      $userAgent = $_SERVER['HTTP_USER_AGENT'];

      
      
      try {
        $model = Landing::where('slug',$args['hash'])->firstOrFail();

        $model->typeform_host = 'smix.typeform.com';
        // si el campo typeform_id es una url, se obtiene el id del de la url 
        if( filter_var($model->typeform_id, FILTER_VALIDATE_URL) ) {
          $model->typeform_host = parse_url($model->typeform_id, PHP_URL_HOST);
          $id =  explode('/', $model->typeform_id) ;
          $model->typeform_id   = array_pop($id);
          // obtener el host de la url
        }

        $template = str_replace('.','_',\App\Loader::getContainer()['hostname']);
        $this->layout = 'landing/layout_' . $template;
        $model->template_id = $template;
        //debug($this->layout);
        //$this->layout = 'landing/layout_' . $model->template_id;
        $this->view->set('item', $model);
        
        if( isset($model->config['login_type']) && $model->config['login_type'] != '' )
          $login_type = $model->config['login_type'];

        
        $nocu = true;  
        if( ($i = $request->getParam('ic')) !== null ){
          $nocu = false;
          $login_type = 'none';
        }
        // Define el tipo de login 
        $this->view->set('login_type', $login_type);
        $this->view->set('nocu', $nocu);
        
        return $this->render( $response, 'landing/preview' );
      } catch (\Exception $e) {
        echo $e->getMessage();die;
        return $response->withRedirect( $this->router->pathFor('pulso.encuestas.page_error') );
      }
      
    }

    public function page_error( $request, $response, $args )
    {
      return $this->render( $response, 'pulso/404' );
    }
    
    private function test_charset()
    {
      $fields_tf = DB::select(DB::raw("
        SELECT tf.title 
        FROM typeform_fields tf
      " ));
      
      foreach( $fields_tf as $field ){
        echo $field->title . "<br>";
        echo utf8_decode($field->title) . "<br>";
        
      }
      die;
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
