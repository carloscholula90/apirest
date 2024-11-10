<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDireccion extends Model
{
    use HasFactory;
    protected $table = 'tipoDireccion';
    protected $primaryKey = 'idTipoDireccion';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idTipoDireccion',
                        'descripcion'
    ];
}
