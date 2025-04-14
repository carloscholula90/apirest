<?php

namespace App\Models\seguridad;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerfilAplicaciones extends Model
{
    use HasFactory;

    protected $table = 'perfilAplicaciones';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'idPerfil',
        'idAplicacion'
    ];

}
