<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultTurno extends Model
{
    use HasFactory;

    protected $table = 'defaultTurno';
    protected $primaryKey = 'idTurno';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idTurno',
        'horaInicio',
        'idTipoBloque',
        'horaFin',
        'activo',
    ];

    public function tipoBloque()
    {
        return $this->belongsTo(TipoBloque::class, 'idTipoBloque', 'idTipoBloque');
    }
}
