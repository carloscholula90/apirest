<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoCuenta extends Model
{
    use HasFactory;
    protected $table = 'edocta';
    public $incrementing = false;
    protected $fillable = [ 'uid','secuencia','idServicio','consecutivo','importe','idPeriodo','fechaMovto','referencia',
                            'idformaPago','cuatrodigitos','tipomovto','FechaPago','folio'];
    public $timestamps = false;
}
