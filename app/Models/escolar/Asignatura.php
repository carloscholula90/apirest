<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asignatura extends Model
{
    use HasFactory;
    protected $table = 'asignatura';
    protected $primaryKey = 'idAsignatura';    
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = true;

    protected $fillable = [
                        'idAsignatura',
                        'descripcion'
    ];
}
