<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Model;

// Un renglon por sesion de clase del grupo
class Horario extends Model
{
    protected $table = 'horarios';
    protected $primaryKey = 'secHorarios';
    public $timestamps = false; // fechaAlta y fechaModificacion las llena la base

    protected $fillable = [
        'grupoSec',
        'fechaIni',
        'fechaFin',
        'day',
        'impartido',
    ];
}
