<?php
namespace App\Command;

use App\Model\Client AS Model;
use App\Model\LandingResponses;
use App\Model\LandingExport;
use App\Model\Landing;
use Illuminate\Database\Capsule\Manager AS DB;

use App\Component\Prosumia\Typeform AS TfComponent;

class Export extends Command
{
  protected $signature = "pulso:export_task";

  protected $description = "Busca tareas para exportar resultados";
  
  private $header = [];
  private $headerNames = [];
  
  public function handle()
  {
    ignore_user_abort(1); // run script in background
    set_time_limit(0);    // set limit to 0
    $start_time = microtime(true);
    while(true) {
      $current_time = microtime(true);
      if( $current_time -  $start_time <= 55 ) {
        $this->run_batch();
        sleep(1);
      } else {
        break;
      }
    }
  }
  
  private function run_batch()
  {
    $pending_tasks = LandingExport::where('status', 'pending')->get();
    
    foreach( $pending_tasks AS $task ) {
      $task->status = 'in progress';
      $task->save();
      $this->process_task($task);
      
      $task->status = $task->processed == $task->total || $task->processed > $task->total ? 'completed' : 'pending';
      
      $task->save();
    }
  }
  private function process_task( $task )
  {
    $this->setHeader( $task );
    
    if( $task->last_id == null ) {
      // crear archivo con ecabezados
      $this->generate_init_file( $task );
    }
    $this->populate_file( $task );
  }
  
  private function setHeader($task) {
    $landing = Landing::findOrFail($task->landing_id);
    // obtener metadata del form 
    $fields = json_decode( utf8_encode($landing->tf_fields) );
    
    $this->headerNames = [
      'id',
      'tf_response_id',
      'fb_user',
      'fb_email',
      'fb_name',
      'fecha',
      'nocu',
      'i',
      't'
    ];
    
    $n = count($this->headerNames);
    foreach( $fields AS $field ) {
      if( in_array($field->type, ['group', 'matrix']) ) {
        foreach( $field->properties->fields AS $childField ) {
          $this->header[$n] = $childField->id;
          $this->headerNames[$n] = 'survey_'.$n.'_'.$childField->title;
          $n++;
        }
      } else {
        $this->header[$n] = $field->id;
        $this->headerNames[$n] = 'survey_'.$n.'_'.$field->title;
        $n++;
      }
    
    }
  }
  
  /**
     * Se empieza del numero mas bajo que es le que podemos identificar en todos los casos
   */
  private function populate_file( $task )
  {
    $path = ROOT . DS . 'export' . DS;
    $filename_export = $path . "export-{$task->landing_id}.csv";
    $handle = fopen($filename_export, 'a');
    
    $last_id = $task->last_id == null ? 0 : $task->last_id;
    // Obtenemos el chunck de esta vuelta y seteamos el last id 
    DB::enableQueryLog();
    $responses = LandingResponses::where('landing_id', $task->landing_id)
                                 ->where('id', '>', $last_id)
                                 ->where('updated_at', '<=', $task->cut_date)
                                 ->orderBy('id', 'ASC')
                                 ->limit(300)
                                 ->get();

    $rows = [];
    foreach( $responses AS $_response ) {
      // $this->info($_response->id);
      $user_data     = json_decode( utf8_encode( $_response->user_data ) ) ;
      $response_json = json_decode( utf8_encode( $_response->response_json ) ) ;
      if( isset( $response_json->items[0]->answers ) ) {
        $row = [
          $_response->id,
          $_response->response_id,
          $user_data->fb->userID,
          ( isset($user_data->fb->email) ? $user_data->fb->email : null ),
          ( isset($user_data->fb->name) ? $user_data->fb->name : null ),
          $_response->created_at->format('Y-m-d'),
          ( isset( $response_json->items[0]->hidden->nocu ) ? $response_json->items[0]->hidden->nocu : null ),
          ( isset( $response_json->items[0]->hidden->i ) ? $response_json->items[0]->hidden->i : null ),
          ( isset( $response_json->items[0]->hidden->f ) ? $response_json->items[0]->hidden->f : null )
        ];
        
        // Seteamos todas las filas y que se reemplazen si existen
        foreach( $this->header AS $n => $id ) {
          if( !isset($row[$n]))
            $row[$n] = '-';
        }
        
        foreach( $response_json->items[0]->answers AS $answer ) {
          $index = array_search($answer->field->id, $this->header);
          
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
          if( $index !== false ) {
            $row[$index] = $res;
          }
        }
        
        
        fputcsv($handle, array_map('utf8_decode',array_values($row)), ';'); 
      }
      
      $task->last_id = $_response->id;
      $task->processed = $task->processed + 1;
      $task->save();
    }
    
    fclose($handle);
    
  }
  
  private function generate_init_file( $task )
  {
    // Contar la cantidad de registros a procesesar
    $total = LandingResponses::where('landing_id', $task->landing_id)
                                 ->where('updated_at', '<=', $task->cut_date)
                                 ->count();
    
    $task->total = $total;
    $task->save();
    
    $path = ROOT . DS . 'export' . DS;
    $filename_export = $path . "export-{$task->landing_id}.csv";
    unlink($filename_export);
    $handle = fopen($filename_export, 'w');
    
    // UTF-8 BOOm
    fputs( $handle, "\xEF\xBB\xBF" );
    // Headers 
    fputcsv($handle, array_map('utf8_decode',array_values($this->headerNames)), ';'); 
    
    fclose($handle);
    
  }
  
  // * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
  // * * * * * cd /var/www/panel.pulso.social && php artisan typeform:cron
}
