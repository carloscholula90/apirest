<?php

namespace App\Models\nomina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallePago extends Model
{
    use HasFactory;

    protected $table = 'detallePago';
    protected $primaryKey = 'idDetallePago';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'idPago',
        'idRegimenFiscalImpuesto',
        'idImpuesto',
        'descripcion',
        'porcentaje',
        'esRetencion',
        'importe',
        'fechaCreacion',
    ];
}
