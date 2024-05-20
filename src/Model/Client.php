<?php

namespace App\Model;

use Illuminate\Support\Str;

use Illuminate\Database\Capsule\Manager AS DB;

class Client extends Model
{
    protected $table = "clients";
    
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new \App\Scope\DateNotNull);
        static::addGlobalScope(new \App\Scope\UserActive);
    }

    public function typeform_answers()
    {
      return $this->hasMany(TypeformAnswer::class,  'form_id', 'typeform_id' )
        ->selectRaw('form_id, COUNT(DISTINCT hash_id) AS total');
    }

    public function scopeToday( $query )
    {
      $query->whereBetween('date', [ date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')]);
      return $query;
    }
    
    public function scopeYesterday( $query )
    {
      $query->whereBetween('date', [ date('Y-m-d 00:00:00', strtotime('-1 days')), date('Y-m-d 23:59:59', strtotime('-1 days'))]);
      return $query;
    }
    
    public function scopeWeek( $query )
    {
      $query->whereBetween('date', [ date('Y-m-d 00:00:00' , strtotime('-7 days')), date('Y-m-d 23:59:59')]);
      return $query;
    }

    public function scopeGroupName( $query, $name )
    {
      $query->leftjoin('typeform', 'typeform.typeform_id', '=', 'clients.typeform_id');
      $query->where('typeform.group_name', $name);
      return $query;
    }
    
    public function scopePais( $query, $name )
    {
      if( strtolower($name) != 'otros' ) {
        if( $name != '' ) {
          $query->where('geoplugin_countryName', $name);
        }
      } else {
        $query->whereNotIn('geoplugin_countryName', ['Argentina', 'Ecuador', 'Peru', 'Guatemala', 'Mexico']);
      }
      return $query;
    }
    
    public function scopeGrupo_riesgo($query, $grupo)
    {
      switch ($grupo) {
        case 'alto':
          $query->where('puntaje', '>=', 20);
          break;
      
        case 'medio':
          $query->where('puntaje', '>=', 10);
          break;
      
        case 'bajo' :
          $query->where('puntaje', '<', 10);
          break;
      }
      
      return $query;
    }

    public function scopeAsintomaticos( $query )
    {
      $query->join('typeform_answers', 'typeform_answers.hash_id', '=', 'clients.hash')
            ->where('field_id', 'CbHCCqCacoGe')
            ->where('value', '<>' ,'Ninguno de los anteriores');
      return $query;
    } 
    
    public function scopePatologicos( $query )
    {
      $query->join('typeform_answers', 'typeform_answers.hash_id', '=', 'clients.hash')
            ->where('field_id', 'YyXX583r1Rmq')
            ->where('value', '<>' ,'Ninguna');
      return $query;
    }    
    
    public function scopeGender( $query, $gender )
    {
      
      if( $gender == 'femenino' ) {
        $genderSearch = ['female', 'Mujer'];
      }else{
        $genderSearch = ['male', 'Hombre'];
      }
      
      $query->where( function($q) use ($genderSearch){
        foreach( $genderSearch AS $term ) {
          $q->orWhere('gender', $term);
        }
      });
      return $query;
    }
    
    public function scopeGeneracion( $query, $generacioName )
    {
      switch ($generacioName) {
        case 'silenciosa':
          $query->whereYear('users.birthday', '<', 1949);
          break;
        case 'boomer':
          $query->whereYear('users.birthday', '>=', 1949)
                ->whereYear('users.birthday', '<=', 1964)
                ;
          break;
        case 'x':
          $query->whereYear('users.birthday', '>=', 1965)
                ->whereYear('users.birthday', '<=', 1979)
                ;
          break;
        case 'milennial':
          $query->whereYear('users.birthday', '>=', 1980)
                ->whereYear('users.birthday', '<=', 1994)
                ;
          break;
        case 'centennial':
          $query->whereYear('users.birthday', '>=', 1995)
                ->whereYear('users.birthday', '<=', 2003)
                ;
          break;
        case '16':
          $query->whereYear('users.birthday', '>=', 2004)
                ;
          break;
      }
      
      return $query;
    }
}
