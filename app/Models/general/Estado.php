<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Estado extends Model
{
    use HasFactory;

    protected $table = 'estado';    
    protected $primaryKey = ['idPais', 'idEstado'];
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    // Sobrescribir getKeyName() para retornar el array de claves primarias
    public function getKeyName()
    {  
        return $this->primaryKey;
    }

    // Sobrescribir find() para buscar usando múltiples columnas de clave primaria
    public static function find($idPais, $idEstado)
    {
        return static::where('idPais', $idPais)
                     ->where('idEstado', $idEstado)
                     ->first();
    }


    public function pais()
    {
        return $this->belongsTo(Pais::class, 'idPais', 'idPais');
    }
}
