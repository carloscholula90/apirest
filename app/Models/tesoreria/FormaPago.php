<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormaPago extends Model
{
    use HasFactory;
    protected $table = 'formaPago';
    protected $primaryKey = 'idFormaPago';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idFormaPago',
                        'descripcion',
                        'solicita4digitos',
                        'archivo'
    ];
}
