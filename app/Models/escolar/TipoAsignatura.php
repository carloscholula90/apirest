<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoAsignatura extends Model
{
    use HasFactory;
    protected $table = 'tipoAsignatura';
    protected $primaryKey = 'idTipoAsignatura';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['idTipoAsignatura','descripcion'];
    public $timestamps = false;
}
