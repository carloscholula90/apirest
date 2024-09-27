<?php

namespace App\Models\seguridad;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolSeg extends Model
{
    use HasFactory;

    protected $table = 'rolesseg';
    protected $primaryKey = 'idRol';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'idRol',
        'nombre'
    ];

}