<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    use HasFactory;
    protected $table ='ciudad';
    protected $primaryKey =  ['idPais','idEstado','idCiudad'];
    protected $fillable = ['idPais', 'idEstado','idCiudad','descripcion'];
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

     // Sobrescribir getKeyName() para retornar el array de claves primarias
     public function getKeyName()
     {  
         return $this->primaryKey;
     }
 
     // Sobrescribir find() para buscar usando múltiples columnas de clave primaria
     public static function find($idPais, $idEstado,$idCiudad)
     {
         return static::where('idPais', $idPais)
                      ->where('idEstado', $idEstado)
                      ->where('idCiudad',$idCiudad)
                      ->first();
     }

    public function estado()
    {
        return $this->hasMany(Estado::class, 'idEstado', 'idEstado');
    }

    public function pais()
    {
        return $this->hasMany(Pais::class, 'idPais', 'idPais');
    }

    public function ciudad()
    {
        return $this->hasMany(Ciudad::class, 'idCiudad', 'idCiudad');
    }

    public function codigoPostal()
    {
        return $this->hasMany(CodigoPostal::class, 'idCp', 'idCp');
    }
}
