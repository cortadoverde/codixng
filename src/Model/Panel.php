<?php

namespace App\Model;

use Illuminate\Support\Str;

class Panel extends Model
{
    protected $table = "entornos";


    public function entidades()
    {
        return $this->belongsToMany(Perfil::class, 'entornos_perfiles', 'entorno_id', 'perfil_id');
    }



}