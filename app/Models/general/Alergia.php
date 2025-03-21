<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alergia extends Model
{
    use HasFactory;

    protected $table = 'alergia';
    protected $primaryKey = null; // Laravel no maneja claves compuestas de forma nativa
    public $incrementing = false; // Evita que Laravel trate la clave como autoincremental
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = [
                            'uid',
                            'consecutivo',
                            'alergia'
    ];

    /**
     * Inserción manual porque Laravel no maneja claves compuestas en `save()`
     */
    public function save(array $options = [])
    {
        if (!$this->exists) {
            return static::query()->insert($this->attributes);
        }
        return parent::save($options);
    }

    /**
     * Buscar un registro por clave compuesta
     */
    public static function findComposite($uid, $consecutivo)
    {
        return static::where('uid', $uid)->where('consecutivo', $consecutivo)->first();
    }
}

