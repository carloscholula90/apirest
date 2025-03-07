<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstatusFactura extends Model
{
    use HasFactory;
    protected $table = 'estatusFactura';
    protected $primaryKey = 'idEstatusFactura';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idEstatusFactura',
                        'descripcion'
    ];
}
