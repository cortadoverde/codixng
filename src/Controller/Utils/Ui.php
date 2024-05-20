<?php

namespace App\Controller\Utils;

use App\Component\Prosumia\Facebook;
use App\Component\Prosumia\Google;

class Ui extends \App\Controller\Controller
{
  
  protected $layout = 'layout_bootstrap';
  
  public function components( $request, $response, $args )
  {
    return $this->render( $response, 'utils/components' );
  }

  public function google($request, $response, $args)
  {
    
    $component = new Google();
    $code = $_POST['code'] ?? $_GET['code'] ?? false;
    if( $code !== false ) {
      $component->getToken($code);
    }
    if( isset($_SESSION['landing_id'])) {
      $landing = $_SESSION['landing_id'];
      unset($_SESSION['landing_id']);
      header('Location: /' . $landing);
      die;
    }
  }

  public function fb( $request, $response, $args ) 
  {
    if( $_SERVER['REQUEST_URI'] == '/samu?') {
      echo "<html><script>
          window.location.href = window.location.href.replace('/samu?#','/samu?')
        </script>
      "  ;die;
    }
    
    try {
      $component = new Facebook();
      //code...
      $component->responseCapture();
    } catch (\Throwable $th) {
      //throw $th;
      debug($th->getMessage());
    }
    
    

    return $this->render( $response, 'utils/fb');
    
  }
  
}