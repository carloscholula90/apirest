<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
namespace App\Models\general;

class Salud extends Model{

    use HasFactory;

    protected $table = 'salud';
    protected $primaryKey = null; // Laravel no maneja claves compuestas de forma nativa
    public $incrementing = false; // Evita que Laravel asuma que la clave es autoincremental
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = [
                            'uid',
                            'secuencia',
                            'enfermedad',
                            'medico',
                            'telefono'
    ];

    /**
     * Insertar datos correctamente, ya que `save()` no funciona bien con claves compuestas.
     */
    public function save(array $options = []){
        if (!$this->exists) 
            return static::query()->insert($this->attributes);
        return parent::save($options);
    }

    /**
     * Buscar un registro por clave compuesta
     */
    public static function findComposite($uid, $secuencia) {
        return static::where('uid', $uid)->where('secuencia', $secuencia)->first();
    }
}