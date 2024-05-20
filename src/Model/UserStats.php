<?php

namespace App\Model;

use Illuminate\Support\Str;

class UserStats extends Model
{
    protected $table = "user_stats";
    
    public $timestamps = false;
    
    public static function findOrCreateByUser( $user_id )
    {
      $obj = static::where('user_id', $user_id)->first();
      
      if( ! $obj ) {
        $obj = new static;
        $obj->user_id = $user_id;
        $obj->total_landing = 0;
        $obj->total_landing_completed = 0;
        $obj->total_cuitificados = 0;
        $obj->save();
      }
      
      return $obj;
    }

}