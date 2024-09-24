<?php

namespace App\Models\seguridad;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolSeg extends Model
{
    use HasFactory;

    protected $table = 'rolesseg';

    protected $fillable = [
        'idRol',
        'nombre'
    ];

}