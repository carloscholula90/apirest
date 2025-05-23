<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;
    protected $table = 'grupos';
    protected $primaryKey = 'grupoSec';
    public $incrementing = false; // si la llave primaria no es autoincremental
    protected $keyType = 'int'; // o 'int' si es un número

    protected $fillable = [
                        'idNivel',
                        'idPeriodo',
                        'idAsignatura',
                        'grupo',
                        'uidProfesor',
                        'secuenciaProfesor',
                        'inscritos',
                        'capacidad',
                        'idIdioma',
                        'uidSecretario',
                        'uidPresidente',
                        'uidSupervisor',
                        'idFormato',
                        'fechaIni',
                        'horaIni',
                        'horaFin',
                        'idTurno',
                    ];

    public $timestamps = false; // si no usas created_at y updated_at
}
