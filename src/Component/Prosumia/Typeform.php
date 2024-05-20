<?php

namespace App\Component\Prosumia;


class Typeform
{
    private $httpClient;

    private $version = 'v1';

    private $base_uri = 'https://api.typeform.com';

    public function __construct( $token = false )
    {
        $container = \App\Loader::getContainer();

        $this->token = $token == false ? $container->typeform['token'] : $token;

        $this->httpClient = new \GuzzleHttp\Client([
          'base_uri' => $this->base_uri,
          'headers' => array(
              'Authorization' => 'Bearer ' . $this->token
          )
        ]);
    }

    public function find( $typeform_id, $query = null, $after = null, $page_size = 1000 )
    {

      $query_arr = [
        'page_size' => $page_size
      ];
      
      if( $query !== null ) 
        $query_arr['query'] = $query;
      
      if( $after !== null || $after != '') {
        $query_arr['before'] = $after;
        
      }
      

      $request_data = array(
          'query' => $query_arr
      );

      try {
        $res = $this->httpClient->request('GET','/forms/' . $typeform_id . '/responses', $request_data);

      } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
      }


      return json_decode( $res->getBody()->getContents() );

    }
    
    public function get_answers( $typeform_id )
    {
      try {
        $res = $this->httpClient->request('GET','/forms/' . $typeform_id );

      } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
      }


      return json_decode( $res->getBody()->getContents() );
    }
    
    public function get_response( $typeform_id, $response_id )
    {
      $request_data = array(
          'query' => array(
              'included_response_ids' => $response_id
          )
      );
      try {
        $res = $this->httpClient->request('GET','/forms/' . $typeform_id .'/responses', $request_data );

      } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
      }


      return json_decode( $res->getBody()->getContents() );
    }
    
    public function get_report( $typeform_id )
    {
      try {
        $res = $this->httpClient->request('GET','/insights/'.$typeform_id.'/summary' );

      } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
      }


      return json_decode( $res->getBody()->getContents() );
    }
    
    public function register_webhook( $typeform_id, $landing_id )
    {
      $data = [
          'json' => [
              'url' => 'https://pulso.prosumia.la/typeform/process/' . $landing_id
          ]
      ];
      
      try {
        $res = $this->httpClient->request('PUT','/forms/' . $typeform_id . '/webhooks/landing-'.$landing_id, $data );
      } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
      }
      return $res;
    }
    
    public function get_webhooks( $typeform_id ) {
      try {
        $res = $this->httpClient->request('GET','/forms/' . $typeform_id . '/webhooks', $data );
      } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
      }
      return $res;
    }


}
