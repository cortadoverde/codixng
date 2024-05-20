<?php

namespace App\Model;

class TypeformAnswer extends Model
{
    protected $table = "typeform_answers";

    protected $fillable = ['form_id', 'field_id', 'hash_id', 'value'];

    public $timestamps = false;
    //
    // public function entidad()
    // {
    //     return $this->belongsTo(Entidad::class);
    // }
}
