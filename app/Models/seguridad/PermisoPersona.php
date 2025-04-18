<?php

namespace App\Models\seguridad;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermisosPersona extends Model
{
    use HasFactory;  
    protected $table = 'permisosPersona';
    public $incrementing = false;  
    public $timestamps = false;

    protected $fillable = [
        'uid',
        'secuencia',
        'idAplicacion'
    ];
}
