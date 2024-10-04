<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder; // Asegúrate de importar la clase Builder
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Estado extends Model
{
    use HasFactory;

    protected $table = 'estado';    
    protected $primaryKey = ['idPais', 'idEstado'];
    protected $fillable = ['idPais', 'idEstado', 'descripcion'];
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    // Sobrescribir el método para manejar claves compuestas en la consulta
    protected function setKeysForSaveQuery($query) // Elimina la especificación de tipo
    {
        $query->where('idPais', '=', $this->getAttribute('idPais'))
              ->where('idEstado', '=', $this->getAttribute('idEstado'));

        return $query;
    }

    // No necesitas sobrescribir getKeyName()
    public static function find($idPais, $idEstado)
    {
        return static::where('idPais', $idPais)
                     ->where('idEstado', $idEstado)
                     ->first();
    }

    public static function max($idPais)
    {
        return static::where('idPais', $idPais)
                     ->max('idEstado');
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'idPais', 'idPais');
    }
}
