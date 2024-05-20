<?php

namespace App\Model;

class Landing extends Model
{
    protected $table = "landing";
    
    public $timestamps = false;
    
    protected $casts = [
        'config' => 'array',
    ];

    public function landing_stats()
    {
      return $this->hasOne(LandingStats::class, 'landing_id');
    }

    public function getLinkAttribute()
    {
      $domain = isset( $this->config['domain'] ) ? $this->config['domain'] : 'web.pulso.social';
      return "https://{$domain}/{$this->slug}";
    }
}