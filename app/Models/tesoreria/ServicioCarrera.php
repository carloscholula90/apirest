<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicioCarrera extends Model
{
   
   use HasFactory;
   protected $table = 'servicioCarrera';
   protected $keyType = 'int';
   public $timestamps = false;
   public $incrementing = true;

    protected $fillable = [
                        'idNivel',
                        'idPeriodo',
                        'idCarrera',
                        'idServicio',
                        'idTurno',
                        'semestre',
                        'monto',
                        'aplicaIns',
                        'fechaAlta',
                        'fechaModificacion'
    ];
}
