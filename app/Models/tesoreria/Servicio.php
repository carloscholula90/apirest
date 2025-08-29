<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class servicio extends Model
{
   use HasFactory;
    protected $table = 'servicio';
    protected $primaryKey = 'idServicio';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idServicio',
                        'descripcion',
                        'efectivo',
                        'tarjeta',
                        'cargoAutomatico'
    ];
}
