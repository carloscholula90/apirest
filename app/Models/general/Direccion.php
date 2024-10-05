<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;  
use Illuminate\Database\Eloquent\Model;
   
class Direccion extends Model    
{

    use HasFactory;  
    protected $table = 'direcciones';    
    protected $fillable = ['uid', 'idParentesco','idTipoDireccion','consecutivo',
                           'idPais', 'idEstado', 'idCiudad','idCp','noExterior','noInterior'];
    public $timestamps = false;    

     // Sobrescribir find() para buscar usando múltiples columnas de clave primaria
     public static function find($idPais, $idEstado,$idCiudad,$idParentesco,$idTipoDireccion,$consecutivo)
     {
         return static::where('idPais', $idPais)
                      ->where('idEstado', $idEstado)   
                      ->where('idCiudad',$idCiudad)
                      ->where('idParentesco',$idParentesco)
                      ->where('idTipoDireccion',$idTipoDireccion)
                      ->where('consecutivo',$consecutivo)
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

}
