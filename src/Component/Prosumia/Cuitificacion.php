<?php

namespace App\Component\Prosumia;


class Cuitificacion
{
    private $httpClient;


    private $version = 'v1';


    public function __construct()
    {
        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => "https://lab.prosumia.la",
            'headers' => array(
                'Authorization' => 'Basic YXBpLXVzZXI6MTY2YmQwM2ZmMmZkMw=='
                                          
            )
        ]);
    }

    public function cuitificar( $birthdate, $document_last_digits, $params_extras = [] )
    {
      $form_params = array_merge(
        [
          'birthdate' => $birthdate,
          'document_last_digits' => $document_last_digits
        ],
        $params_extras
      );
      
      try {
        $res = $this->httpClient->request('POST', '/sf/cuitify', [
          'form_params' => $form_params,
          'headers' =>[
            'Authorization' => 'Basic YXBpLXVzZXI6MTY2YmQwM2ZmMmZkMw==',
          ]
        ]);  
      } catch (\Exception $e) {
        return false;
      }
      
      
      return json_decode($res->getBody()->getContents());
    }
    
    public function is_stored( $email )
    {
      $form_params = [
        'email' => $email
      ];
      
      try {
        $res = $this->httpClient->request('POST', '/sf/is_stored', [
          'form_params' => $form_params,
          'headers' =>[
            'Authorization' => 'Basic YXBpLXVzZXItYzpLNVFYaGhaRzFC',
          ]
        ]);  
      } catch (\Exception $e) {
        return false;
      }
      
      $response = json_decode($res->getBody()->getContents());
      if( isset($response->found ) ) return $response->found;
      return false;
      
    }

}