<?php

namespace App\Component\Prosumia;


class Rapiwhaa
{
    private $httpClient;

    private $version = 'v1';

    private $base_uri = 'https://api.typeform.com';

    public function __construct()
    {
        $container = \App\Loader::getContainer();

        $this->token = $container->typeform['token'];

        $this->httpClient = new \GuzzleHttp\Client([
          'base_uri' => $this->base_uri,
          'headers' => array(
              'Authorization' => 'Bearer ' . $this->token
          )
        ]);
    }

    public function find( $typeform_id, $query )
    {

      $request_data = array(
          'query' => array(
              'query' => $query
          )
      );

      try {
        $res = $this->httpClient->request('GET','/forms/' . $typeform_id . '/responses', $request_data);

      } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
      }


      return json_decode( $res->getBody()->getContents() );

    }


}
