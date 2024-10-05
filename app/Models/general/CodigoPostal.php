<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{  
    use HasFactory;
    protected $table ='codigoPostal';
    protected $primaryKey =  ['idPais','idEstado','idCiudad','idCp'];
    protected $fillable = ['idPais', 'idEstado','idCiudad','descripcion','idAsentamiento'];
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

     
     // Sobrescribir find() para buscar usando múltiples columnas de clave primaria
     public static function find($idPais, $idEstado,$idCiudad,$idCp)
     {
         return static::where('idPais', $idPais)
                      ->where('idEstado', $idEstado)
                      ->where('idCiudad',$idCiudad)
                      ->where('idCp',$idCp)
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

    public function asentamiento()
    {
        return $this->hasMany(Asentamiento::class, 'idAsentamiento', 'idAsentamiento');
    }
}
