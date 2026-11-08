<?php

namespace App\Models\nomina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pago';
    protected $primaryKey = 'idPago';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'idCalendarioPago',
        'uid',
        'idRegimenFiscal',
        'costoPorHora',
        'totalHoras',
        'horasADescontar',
        'netoAPagar',
        'fechaCreacion',
    ];
}
