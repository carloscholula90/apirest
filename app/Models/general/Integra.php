<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integra extends Model
{
    use HasFactory;

    protected $table = 'integra';
    protected $primaryKey = null; // Laravel no maneja claves compuestas
    public $incrementing = false; // Evita que Eloquent asuma autoincremento
    public $timestamps = false;

    protected $fillable = [
        'uid',
        'secuencia',
        'idRol',
        'idPerfil'
    ];

    // Sobreescribir el método `setAttribute`
    public function setAttribute($key, $value)
    {
        if ($key === null) {
            throw new \InvalidArgumentException("El nombre del atributo no puede ser null.");
        }
        return parent::setAttribute($key, $value);
    }

    // Sobreescribir el método `save` porque Laravel no maneja claves compuestas
    public function save(array $options = [])
    {
        if (!$this->exists) {
            return static::query()->insert($this->attributes);
        }
        return parent::save($options);
    }

    // Para encontrar un registro con clave compuesta
    public static function findComposite($uid, $secuencia)
    {
        return static::where('uid', $uid)->where('secuencia', $secuencia)->first();
    }
}
