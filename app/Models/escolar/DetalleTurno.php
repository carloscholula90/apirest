<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleTurno extends Model
{
    use HasFactory;

    protected $table = 'dtlTurno';
    protected $primaryKey = 'idDtlTurno';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'idDtlTurno',
        'idTurno',
        'diaSemana',
        'horaInicio',
        'horaFin',
        'idNivel',
    ];

    /*
     * Días del turno que aplican a un nivel: los configurados para ese nivel y, si el nivel no tiene,
     * los que no tienen nivel (generales). Sin nivel, solo los generales. Puede regresar vacío.
     */
    public static function paraNivel($idTurno, $idNivel)
    {
        $filas = static::where('idTurno', $idTurno)->get();

        if ($idNivel) {
            $delNivel = $filas->filter(function ($fila) use ($idNivel) {
                return (int) $fila->idNivel === (int) $idNivel;
            });

            if ($delNivel->isNotEmpty()) {
                return $delNivel->values();
            }
        }

        return $filas->whereNull('idNivel')->values();
    }
}
