<?php

namespace App\Model;

use Illuminate\Support\Str;

class LandingResponses extends Model
{
    protected $table = "landing_responses";
    
    public function landing()
    {
      return $this->hasOne(Landing::class, 'id', 'landing_id');
    }
}