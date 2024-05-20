<?php

namespace App;

abstract class Loader
{

  public static $paths = array();

  public static $app;

  public static function setApp( $app )
  {
    self::$app = $app;
  }

  public static function getContainer()
  {
    return self::$app->getContainer();
  }

  public static function setPaths( array $paths )
  {
    self::$paths = array_merge( self::$paths, $paths );
  }

  public static function load( $path = false )
  {
    if( $path === false ) {
      foreach( array_keys( self::$paths ) AS $namespace )
      {
        self::loadPath( $namespace );
      }
      return;
    }
    return self::loadPath( $path );
  }

  public static function loadPath( $path )
  {

    if( ! isset ( self::$paths[$path] ) )
      return null;

    foreach( self::$paths[$path] as $ns => $relative_path )
    {
      if( is_string( $relative_path ) ) {
        self::recursiveLoad( $relative_path );
      } else {
        if( isset( $relative_path['dir'] ) ) {
          $exclude = isset( $relative_path['exclude'] ) ? $relative_path['exclude'] : [];
          foreach( (array) $relative_path['dir'] AS $path ) {
            self::recursiveLoad( $path , $exclude );
          }
        }
      }
    }
    return;
  }

  public static function recursiveLoad( $path, $exclude = array() )
  {
    $app       = self::$app;
    $real_path = rtrim( APP_DIR . DS . $path, '//');
    if( is_dir( $real_path ) ) {
      foreach( glob( "{$real_path}/*.php" ) as $file ) {
      if( !in_array( rtrim( basename($file), '.php' ), $exclude ) )
        if( file_exists( $file ))
          include_once $file;
      }
    }
  }

}
