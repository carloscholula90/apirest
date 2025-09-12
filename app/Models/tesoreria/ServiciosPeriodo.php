<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiciosPeriodo extends Model
{
   
   use HasFactory;
   protected $table = 'serviciosPeriodo';
   protected $keyType = 'int';
   public $timestamps = false;
   public $incrementing = false;
   protected $primaryKey = null;

    protected $fillable = [
                        'idNivel',
                        'idPeriodo',
                        'idServicio',
                        'monto'
    ];
}
