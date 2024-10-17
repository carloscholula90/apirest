<?php

namespace App\Models\seguridad;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermisoRol extends Model
{
    use HasFactory;

    protected $table = 'permisosRol';
    protected $primaryKey = ['idAplicacion', 'idRol'];
    public $incrementing = false;
    protected $fillable = [ 'idAplicacion','idRol'];
    public $timestamps = false;
}
