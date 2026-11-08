<?php

namespace App\Models\nomina;

use App\Models\escolar\Turno;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarioPago extends Model
{
    use HasFactory;

    protected $table = 'calendarioPago';
    protected $primaryKey = 'idCalendarioPago';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'idTurno',
        'fechaInicio',
        'fechaFin',
        'procesado',
        'fechaCreacion',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'idTurno', 'idTurno');
    }
}
