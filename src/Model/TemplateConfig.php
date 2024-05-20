<?php

namespace App\Model;

use Illuminate\Support\Str;

class TemplateConfig extends Model
{
    protected $table = "template_config";

    public $timestamps = false;
    
    protected $casts = [
        'config_json' => 'array',
    ];
    
    public function landing()
    {
        return $this->belongsToMany(Landig::class)->withPivot('config_json');
    }

    


}