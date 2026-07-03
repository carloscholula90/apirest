<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoBloque extends Model
{
    use HasFactory;

    protected $table = 'tipoBloque';
    protected $primaryKey = 'idTipoBloque';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'idTipoBloque',
        'descripcion',
        'color',
        'activo',
    ];
}
