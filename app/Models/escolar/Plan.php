<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $table = 'plan';   
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                            'idPlan',
                            'idCarrera',
                            'descripcion',
                            'rvoe',
                            'fechainicio',
                            'idNivel',
                            'idModalidad',
                            'semestres',
                            'vigente',
                            'estatal',
                            'decimales',
                            'minAprobatoria',
                            'grado'
                        ];

}
