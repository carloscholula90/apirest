<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    use HasFactory;

    protected $table = 'empleado';
    protected $primaryKey = null; // Laravel no maneja claves compuestas de forma nativa
    public $incrementing = false; // Evita que Laravel trate la clave como autoincremental
    public $timestamps = false;
    
    protected $fillable = [
                            'uid',
                            'secuencia',
                            'fechainicio',
                            'fechabaja',
                            'idTipoContrato',
                            'gradoEstudios',
                            'idPuesto'
    ];

    /**
     * Buscar un registro por clave compuesta
     */
    public static function findComposite($uid, $secuencia)
    {
        return static::where('uid', $uid)->where('secuencia', $secuencia)->first();
    }
}

