<?php
namespace App\Command;

use App\Model\Client AS Model;
use App\Model\LandingResponses;
use Illuminate\Database\Capsule\Manager AS DB;

use App\Component\Prosumia\Typeform AS TfComponent;

class Cron extends Command
{
  protected $signature = "typeform:cron";

  protected $description = "Componente para crear usuarios";
  
  public function handle()
  {
    $items = LandingResponses::where('response_json->total_items', 0)->get();
    $component = new TfComponent();
    foreach( $items AS $n => $response ) {
      $response_json = $component->get_response( 'oB5qiWJ4', $response->response_id );
      $response->response_json = json_encode($response_json);
      $response->save();
    }
  }
  
  // * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
  // * * * * * cd /var/www/panel.pulso.social && php artisan typeform:cron
}
