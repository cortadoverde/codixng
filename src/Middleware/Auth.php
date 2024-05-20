<?php

namespace App\Middleware;

class Auth 
{

    public function __construct( $container )
    {
        $this->container = $container;
    }

    public function __invoke( $request, $response, $next )
    {
        // Ontener la ruta
        $route     = $request->getAttribute('route');
        $routeName = $route->getName();

        if( ! isset ( $_SESSION['user'] ) ) {
            $response = $response->withRedirect( $this->container->router->pathFor('login') );
        } else {

            $this->user = $_SESSION['user'];
                        
            $response = $next($request, $response);

            if( $routeName == 'login' && isset( $_SESSION['user'] ) ) {
                $response = $response->withRedirect( $this->container->router->pathFor('admin.dashboard') );
            }

        }

        return $response;
    }

}