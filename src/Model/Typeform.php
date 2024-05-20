<?php

namespace App\Model;

class Typeform extends Model
{
    protected $table = "typeform";

    protected $fillable = ['form_id', 'field_id', 'hash_id', 'value'];

    public $timestamps = false;
    //
    // public function entidad()
    // {
    //     return $this->belongsTo(Entidad::class);
    // }
}
