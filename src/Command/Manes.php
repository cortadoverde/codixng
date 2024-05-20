<?php
namespace App\Command;

use App\Model\Client AS Model;
use App\Model\LandingResponses;
use Illuminate\Database\Capsule\Manager AS DB;

use App\Component\Prosumia\Typeform AS TfComponent;

class Manes extends Command
{
  protected $signature = "typeform:manes";

  protected $description = "Captura el formulario";
  
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
    $data = \App\Model\TfDarelpaso::orderBy('id','DESC')->limit(1)->get();
    if( $data->count() == 0 ) return;
    
    $token = '5NfzZJv29xKLzkpvHU3Z83GFEUXmoUnh36KjBQhfgMKD'; //$this->ask('token');
    $typeform_id = 'UAW7g9Fj';//$this->ask('typeform_id');
    $response_id = $data[0]->response_id;
    $typeformComponent = new \App\Component\Prosumia\Typeform($token);
    foreach($typeformComponent->find($typeform_id, null, null, 100)->items AS $restf ) {
      $count = \App\Model\TfDarelpaso::where('response_id', $restf->token)->count();
      if( $count == 0 ) {
        $item = new \App\Model\TfDarelpaso;
        $item->response_id = $restf->token;
        $item->playload = json_encode($restf);
        $item->tf_submitted_at = str_replace('Z', '', $restf->submitted_at);
        $item->save();
      }
    }
    
  }
  
  // * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
  // * * * * * cd /var/www/panel.pulso.social && php artisan typeform:manes  >> /dev/null 2>&1
}
