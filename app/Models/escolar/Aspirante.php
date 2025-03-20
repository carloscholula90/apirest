<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aspirante extends Model
{
    use HasFactory;
    protected $table = 'aspirante';
    protected $primaryKey = null;    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                            'uid' ,
                            'secuencia',
                            'idPeriodo' ,
                            'idCarrera' ,
                            'adeudoAsignaturas' ,
                            'idNivel',
                            'idMedio',
                            'publica',
                            'paisCursoGradoAnterior',
                            'estadoCursoGradoAnterior',
                            'uidEmpleado',
                            'fechaSolicitud',
                            'matReprobada' ,
                            'mesReprobada',
                            'idNivelAnterior',
                            'escuelaProcedencia' 
    ];
}
