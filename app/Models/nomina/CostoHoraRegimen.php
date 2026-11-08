<?php

namespace App\Models\nomina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostoHoraRegimen extends Model
{
    use HasFactory;

    protected $table = 'costoHoraRegimen';
    protected $primaryKey = 'idCostoHora';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'idRegimenFiscal',
        'costoHora',
        'vigenciaInicio',
        'vigenciaFin',
    ];
}
