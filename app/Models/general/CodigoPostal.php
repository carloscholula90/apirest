<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigoPostal extends Model
{
    use HasFactory;
    protected $table = 'codigoPostal';
    protected $primaryKey = null;    
    protected $keyType = null;
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idPais',
                        'idEstado',
                        'idCiudad',
                        'idCp',
                        'cp',
                        'descripcion',
                        'idAsentamiento'
    ];
}
