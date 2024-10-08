<?php

namespace App\Models\seguridad;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
  
class Aplicacion extends Model
{
    use HasFactory;
    protected $table = 'aplicaciones';
    protected $primaryKey = 'idAplicacion';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
                        'idAplicacion',
                        'descripcion',
                        'activo',
                        'idModulo'
    ];
}
