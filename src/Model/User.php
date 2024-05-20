<?php

namespace App\Model;

use Illuminate\Support\Str;

class User extends Model
{
    protected $table = "users";
    public $timestamps = false;

    public function scopeOauth_provider($query, $oauth_provider)
    {
      $query->where('oauth_provider', $oauth_provider);
      return $query;
    }


}
