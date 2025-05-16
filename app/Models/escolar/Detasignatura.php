<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detasignatura extends Model
{
   
    use HasFactory;
    protected $table = 'detasignatura';
    public $incrementing = true;
    protected $fillable = ['secPlan','idPlan','idCarrera',
                           'idAsignatura','seriacion','ordenk',
                           'semestre','ordenc','condocente',
                           'independientes','creditos','instalaciones'];
    public $timestamps = false;
}
