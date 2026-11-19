<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Model;

// Firmantes predeterminados del acta por nivel (uid de persona); idPresidente ya no se usa: el presidente es el profesor
class ConfiguracionActas extends Model
{
    protected $table = 'configuracionActas';
    protected $primaryKey = null; // La tabla no tiene llave; se identifica por idNivel
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'idNivel',
        'idSecretario',
        'idSupervisor',
    ];
}
