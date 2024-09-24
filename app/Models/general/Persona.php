<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class persona extends Model{
    
    use HasFactory;
    protected $table = 'persona';
    protected $primaryKey = 'uid';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
                            'curp',
                            'nombre',
                            'primerApellido',
                            'segundoApellido',
                            'fechaNacimiento',
                            'sexo',
                            'idPais',
                            'idEstado',
                            'idCiudad',
                            'idEdoCivil',
                            'rfc'
                            ];

    protected $dates = ['fechaNacimiento'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
     
    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }
    
    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function contactos()
    {
        return $this->hasMany(Contacto::class, 'uid', 'uid');
    }
}
