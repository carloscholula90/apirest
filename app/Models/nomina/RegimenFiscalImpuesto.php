<?php

namespace App\Models\nomina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegimenFiscalImpuesto extends Model
{
    use HasFactory;

    protected $table = 'regimenFiscalImpuesto';
    protected $primaryKey = 'idRegimenFiscalImpuesto';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'idRegimenFiscal',
        'idImpuesto',
        'activo',
        'fechaCreacion',
    ];
}
