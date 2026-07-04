<?php

namespace App\Models\nomina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarioPago extends Model
{
    use HasFactory;

    protected $table = 'calendarioPago';
    protected $primaryKey = 'idTurno';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idTurno',
        'fechaInicio',
        'fechaFin',
        'procesado',
        'fechaCreacion',
    ];
}
