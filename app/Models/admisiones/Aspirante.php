<?php

namespace App\Models\admisiones;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aspirante extends Model
{
    use HasFactory;
    protected $table = 'aspirante';
    protected $primaryKey =null;
    public $incrementing = false;
    public $timestamps = false;  

    protected $fillable = [
                        'uid','secuencia','idPeriodo','idCarrera','adeudoAsignaturas','idNivel','idTurno',
                        'idMedio','publica','paisCursoGradoAnterior','estadoCursoGradoAnterior',
                        'uidEmpleado','fechaSolicitud','matReprobada','mesReprobada','observaciones','idNivelAnterior',
                        'escuelaProcedencia'];
}
