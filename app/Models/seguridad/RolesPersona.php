<?php

namespace App\Models\seguridad;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolesPersona extends Model
{
    use HasFactory;

    protected $table = 'rolesPersona';
    protected $primaryKey = ['uid','secuencia','idRol'];
    public $incrementing = false;
    protected $fillable = [ 'uid','secuencia','idRol'];
    public $timestamps = false;
}
