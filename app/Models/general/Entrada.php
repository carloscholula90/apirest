<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrada extends Model
{
    use HasFactory;

    protected $table = 'entradas';
    protected $primaryKey = 'id_entrada';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id_entrada',
        'nombre',
        'ip',
        'estatus',
        'fechaAlta',
        'fechaModificacion',
        'contrasena',
    ];
}
