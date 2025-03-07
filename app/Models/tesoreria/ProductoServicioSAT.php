<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoServicioSAT extends Model
{
    use HasFactory;
    protected $table = 'productoServicioSAT';
    protected $primaryKey = 'idProductoServicio';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idProductoServicio',
                        'descripcion'
    ];
}
