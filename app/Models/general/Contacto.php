<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    use HasFactory;
    protected $table = 'contacto';
    protected $fillable = [
        'uid', 'idParentesco', 'idTipoContacto', 'consecutivo','dato'
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'uid', 'uid');
    }
}

