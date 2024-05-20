<?php

namespace App;

/**
 * Controlador base
 *
 * define el container de la app
 *
 * define el getter para obtener datos del container
 */
class Controller
{
  private $container;

  protected $layout = 'layout_bootstrap';

  public function __construct( $container )
  {
    $this->container = $container;


    if( method_exists( $this , "onConstruct" ) ) {
      call_user_func_array( [ $this, "onConstruct"], [] );
    }

    $this->result = new \StdClass();

    $this->BasePath = $this->request->getUri()->getBasePath() ;

    $this->view->set('BasePath', $this->BasePath);

    $this->view->set('Request', $this->request->getUri());

    $this->view->set('layout', $this->layout);


  }


  public function __invoke($request, $response, $args) {
      $this->request = $request;
      // Get the route info
      $routeInfo = $request->getAttribute('routeInfo');

      // /** @var \Slim\Interfaces\RouterInterface $router */
      $router = $this->router;

      // If router hasn't been dispatched or the URI changed then dispatch
      if (null === $routeInfo || ($routeInfo['request'] !== [$request->getMethod(), (string) $request->getUri()])) {
         $request = $this->dispatchRouterAndPrepareRoute($request, $router);
         $routeInfo = $request->getAttribute('routeInfo');
      }
      $route = $request->getAttribute('route');
      $routeName = $route->getName();

      $controller = mb_strtolower( str_replace('\\', '.', str_replace('App\\Controller\\', '', get_class($this) ) ) );

      $action     = str_replace( $controller . '.', '', $routeName);

      $this->view->set('RouteName', $routeName );

      if( method_exists( $this , $action ) ) {
        return call_user_func_array( [ $this, $action ], [ $request, $response, $args]);
      }else {
        return $response->withRedirect("/");
      }

   }

  public function __get( $value )
  {
    return isset( $this->container[$value] ) ? $this->container[$value] : null;
  }

  public function set( $key, $value )
  {
    $this->container[$key] = $value;
  }

  public function render( $response, $view, $args = [])
  {
    if( $this->request->isXhr()) {
      $this->view->set('layout', 'ajax');
    } else {
      $this->view->set('layout', $this->layout);
    }
    return $this->view->render(  $response, $view, $args );
  }

}