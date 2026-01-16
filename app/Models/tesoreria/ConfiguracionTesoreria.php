<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionTesoreria extends Model
{
    use HasFactory;
    protected $table = 'configuracionTesoreria';
    public $incrementing = false;
    protected $fillable = [ 'idNivel',
                            'idServicioInscripcion',
                            'idServicioColegiatura',
                            'idServicioNotaCargo',
                            'idServicioNotaCredito',
                            'idServicioRecargo',
                            'idServicioReinscripcion',
                            'fechaAlta',
                            'fechaModificacion'];
    public $timestamps = false;
}
