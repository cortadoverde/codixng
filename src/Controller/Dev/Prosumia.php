<?php

namespace App\Controller\Dev;

class Prosumia extends \App\Controller\Controller
{
    
    public function dashboard( $request, $response, $args )
    {
        return $this->render( $response, 'dev/onboarding' );
    }
}
