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
    ];
}
