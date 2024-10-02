<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    use HasFactory;
    protected $table ='ciudad';
    protected $primaryKey =  ['idPais','idEstado','idCiudad'];
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    public function estado()
    {
        return $this->hasMany(Estado::class, 'idEstado', 'idEstado');
    }

    public function pais()
    {
        return $this->hasMany(Pais::class, 'idPais', 'idPais');
    }
}
