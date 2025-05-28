<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;  
use Illuminate\Database\Eloquent\Model;
   
class Direccion extends Model    
{
    use HasFactory;  
    protected $table = 'direcciones';    
    protected $fillable = ['uid', 'idParentesco','idTipoDireccion','consecutivo','calle',
                           'idPais', 'idEstado', 'idCiudad','idCp','noExterior','noInterior'];
    public $timestamps = false; 
    public $incrementing = false;
    protected $primaryKey = null;

    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();

        // Añade todas las claves compuestas a la consulta
        $keys = ['uid', 'consecutivo']; // Aquí defines tus claves

        foreach ($keys as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }  

    public static function find($uid, $consecutivo)
    {
        return static::where('uid', $uid)
                     ->where('consecutivo', $consecutivo)
                     ->first();
    }

    public function delete()
    {
        return $this->getKeyForSaveQuery()->delete();
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
