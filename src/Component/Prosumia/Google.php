<?php

namespace App\Component\Prosumia;


class Google
{
    private $httpClient;


    private $version = 'v1';

    private $version_graph = 'v19.0';

    private $client_id = "";//env("google.client_id");
    private $secret = "";//; env("google.secret");
    public function __construct()
    {
        $this->redirect_uri = 'https://web.pulso.social/google';
        $this->baseParams = [
            'client_id'         => $this->client_id,
            'client_secret'     => $this->secret,
            'redirect_uri'      => 'https://web.pulso.social/samu'
        ];

        $this->httpClient = new \GuzzleHttp\Client();
    }
    /*
    https://www.facebook.com/v19.0/dialog/oauth?
  client_id={app-id}
  &redirect_uri={redirect-uri}
  &state={state-param}
  */

    public function getToken( $code )
    {
        $res = $this->httpClient->post('https://oauth2.googleapis.com/token',[
            'form_params' => [
                'code'      => $code,
                'client_id' => $this->client_id,
                'client_secret' => $this->secret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirect_uri,
                'access_type' => 'offline'
            ]
        ]);
        $data = json_decode($res->getBody()->getContents());
        $this->access_token = $data->access_token;
        $_SESSION['access_token'] = $this->access_token;

        $profile =  $this->getProfile();
        $_SESSION['profile'] = $profile;
        return $profile;
    }

    public function getProfile()
    {
        $res = $this->httpClient->get('https://people.googleapis.com/v1/people/me?requestMask.includeField=person.emailAddresses',  [
            'headers' =>[
              'Authorization' => 'Bearer '. $this->access_token,
              'Accept'     => 'application/json'
            ]
          ]); 
        return json_decode($res->getBody()->getContents()); 
    }
    public function dialog( $redirect, $state )
    {
        $link = "https://www.facebook.com/v19.0/dialog/oauth?client_id={$this->client_id}&redirect_uri={$redirect}&state={$state}&response_type=code&scope=email";
        header('Location: ' . $link);
        die; 
    }

    public function authenticate( $code )
    {

        try {
            $form_params = array_merge(
                [
                  'code' => $code,
                ],
                $this->baseParams
              );


            $res = $this->httpClient->request('GET', '/v19.0/oauth/access_token?' . http_build_query( $form_params), [
                'form_params' => $form_params
            ]);

            $_SESSION['token'] = json_decode($res->getBody()->getContents());

            $this->getData();
             
        } catch (\Exception $e) {
            $_SESSION['token'] = $e->getMessage();
            return false;
        }  
    }

    public function getData()
    {
        try {
        $form_params = array_merge(
            [
              'access_token' => $_SESSION['token']->access_token,
              'fields' =>'id,name,email'
            ], []
          );
        $res = $this->httpClient->request('GET', '/v19.0/me?' . http_build_query( $form_params), [
            'form_params' => $form_params
        ]);

        $json = json_decode($res->getBody()->getContents());
        $_SESSION['userID'] = $json->id;
        $_SESSION['email'] = $json->email ?? 'sin email';
    } catch (\Exception $e) {
        $_SESSION['userID'] = '1';
        $_SESSION['email'] = $e->getMessage();
    }
    }

    public function responseCapture( )
    {
        $path = $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '/samu';
        
        if (isset($_GET['bypass'])) {
            $_SESSION['state'] = $_GET['state'];
            $this->dialog($_GET['bypass'], $_GET['state']);
        } else {

            if (isset($_GET['code'])) {
                $_SESSION['state'] = $_GET['state'];
                $this->authenticate($_GET['code']);
                header('Location: /' . $_GET['state']);
                die;
            }
        }
        if (isset($_GET['logout'])) {
            unset($_SESSION['token']);
        }

        if( isset($_GET['error']) ) {
            $_SESSION['state'] = $_GET['state'];
            session_destroy();
            header('Location: /' . $_GET['state']);
            die;
        }

        $state = $_SESSION['state'];
        unset($_SESSION['state']);
        header('Location: /' . $state);
        die;

        
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