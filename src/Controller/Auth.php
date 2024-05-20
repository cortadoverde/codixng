<?php

namespace App\Controller;

use App\Model\Admin AS User;

class Auth extends \App\Controller
{
    protected $layout = 'layout_bootstrap';
    
    public function login( $request, $response, $args )
    {
        $login_error = false;
        $error_msg   = '';
        if( isset( $_SESSION['login_error'] ) ) {
          $error_msg   = $_SESSION['login_error'];
          unset($_SESSION['login_error']);
          $login_error = true;
        }

        $this->view->set('layout', 'admin/layout');
        $this->view->set('Auth', ['error' => $login_error, 'msg' => $error_msg]);

        return $this->render( $response, 'admin/auth/login' );
    }

    public function check_auth( $request, $response )
    {
        $username = $request->getParam('username', false);
        $password = $request->getParam('password', false);

        if( $username == false || $password == false ) {
          $_SESSION['login_error'] = 'El campo Usuario y Contraseña no pueden estar vacios';
          return $response->withRedirect( $this->router->pathFor('login') );
        }

        if( User::where('username', $username)->where('password', md5( $password ) )->count() === 1 ) {
          $_SESSION['user'] = User::where('username', $username)->where('password', md5( $password ) )->first()->toArray();
        } else {
          $_SESSION['login_error'] = 'Verifique su usuario y/o contraseña';
          return $response->withRedirect( $this->router->pathFor('login') );
        }

        return $response->withRedirect( $this->router->pathFor('admin.dashboard.index') );
    }

    public function logout( $request, $response )
    {
      session_destroy();
      return $response->withRedirect( $this->router->pathFor('admin.dashboard.index') );
    }
}
