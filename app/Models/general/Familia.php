<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Familia extends Model
{
    use HasFactory;

    protected $table = 'familia';

    // Definir la clave primaria compuesta
    protected $primaryKey = null;
    public $incrementing = false;  // Evita que Laravel asuma que las claves son autoincrementales
    public $timestamps = false;  // Si no tienes timestamps

    // Definir los campos que se pueden asignar masivamente
    protected $fillable = [
        'uid', 
        'idParentesco', 
        'tutor', 
        'nombre', 
        'ocupacion',
        'primerApellido', 
        'segundoApellido', 
        'fechaNacimiento', 
        'finado'
    ];

    /**
     * Relación con la tabla Persona (si la tienes)
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'uid', 'uid');
    }

    /**
     * Sobreescribir el método save para trabajar con claves compuestas
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
    public static function findComposite($uid, $idParentesco)
    {
        return static::where('uid', $uid)
            ->where('idParentesco', $idParentesco)
            ->first();
    }
}

