<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Impuesto extends Model
{
    use HasFactory;
    protected $table = 'impuestos';
    protected $primaryKey = 'idImpuesto';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idImpuesto',
                        'descripcion'
    ];
}
