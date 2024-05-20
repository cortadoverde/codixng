<?php

namespace App;

class Debug
{

  public static function dd()
  {
    $arguments = func_get_args();
    $nl = "\n";

    // self::backtrace();


    foreach( $arguments AS $index => $arg ) {
      $type  = gettype( $arg );
      $title = "ARG : " . $index . " type: " . $type . ( ($type == "object") ? ' class:' . get_class( $arg ) : '');
      $subtitle = self::repeat("=", strlen( $title) );

      echo "<pre class='debug_block'>";
        echo $title . $nl;
        echo $subtitle . $nl;
        echo $nl;
        print_r( $arg );
        echo self::repeat($nl,3);
      echo "</pre>";
    }

    die;
  }

  public static function dash( $output )
  {
    $length = [0];
    foreach (explode("\n", $output) as $line) {
      $length[] = strlen( $line );
    }

    return self::repeat( "=", max($length ) );

  }

  public static function repeat( $char, $count )
  {
    return str_repeat( $char, $count );
  }

  
  public static function backtrace()
  {
    $backtrace = debug_backtrace();
    $backtrace = array_slice($backtrace, 3);
    echo "<ul>";
    foreach( $backtrace AS $trace ) {
      echo "<li> {$trace['file']} ::{$trace['line']}";
    }
    echo "</ul>";

  }

  public static function setStyle()
  {

    $style = "
      <style>
        pre.debug_block {
          background: #CCC;
        }
      </style>
    ";

    return $style;
  }
  


}