<?php

namespace App\Models\nomina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegimenFiscalNomina extends Model
{
    use HasFactory;

    protected $table = 'regimenFiscalNomina';
    protected $primaryKey = 'idRegimenFiscal';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'descripcion',
        'activo',
    ];
}
