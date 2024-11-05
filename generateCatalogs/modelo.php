<?php

namespace App\Models\{ruta};

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {Nombre} extends Model
{
    use HasFactory;
    protected $table = '{tabla}';
    protected $primaryKey = 'id{Nombre}';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'id{Nombre}',
                        'descripcion'
    ];
}
