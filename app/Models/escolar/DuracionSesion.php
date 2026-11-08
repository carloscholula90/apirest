<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Model;

class DuracionSesion extends Model
{
    protected $table = 'duracionSesion';
    protected $primaryKey = null; // La llave es compuesta (idNivel, idTurno)
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'idNivel',
        'idTurno',
        'duracion',
    ];

    // Minutos por sesion del nivel y turno; null si la combinacion no esta configurada
    public static function minutos($idNivel, $idTurno)
    {
        $duracion = self::where('idNivel', $idNivel)
            ->where('idTurno', $idTurno)
            ->value('duracion');

        return $duracion !== null ? (int) $duracion : null;
    }
}
