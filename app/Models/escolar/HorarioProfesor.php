<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioProfesor extends Model
{
    use HasFactory;

    protected $table = 'horarioProfesor';
    protected $primaryKey = 'secuencia';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'uid',
        'secuencia',
        'fechaInicio',
        'fechaFin',
        'diaSemana',
        'horaInicio',
        'horaFin',
        'idTipoBloque',
    ];

    public function tipoBloque()
    {
        return $this->belongsTo(TipoBloque::class, 'idTipoBloque', 'idTipoBloque');
    }
}
