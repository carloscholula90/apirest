<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model{

    use HasFactory;
    protected $table = 'usuario';
    public $incrementing = false;
    protected $primaryKey = ['uid','secuencial'];
    public $timestamps = false;

    public function persona(){
        return $this->belongsTo(Persona::class);
    }
    
}