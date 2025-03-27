<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contacto extends Model{
    use HasFactory;

    protected $table = 'contacto';
    
    // No se puede usar una clave primaria compuesta en Laravel directamente
    protected $primaryKey = null;
    public $incrementing = false;  // Evita que Laravel asuma que las claves son autoincrementales
    public $timestamps = false;  // Si no tienes timestamps

    // Definir las claves primarias compuestas
    protected $fillable = [
        'uid', 
        'idParentesco', 
        'idTipoContacto', 
        'consecutivo', 
        'dato'
    ];

    /**
     * Relación con la tabla Persona (suponiendo que existe el modelo Persona)
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
    public static function findComposite($uid, $idParentesco, $idTipoContacto, $consecutivo)
    {
        return static::where('uid', $uid)
            ->where('idParentesco', $idParentesco)
            ->where('idTipoContacto', $idTipoContacto)
            ->where('consecutivo', $consecutivo)
            ->first();
    }
}

