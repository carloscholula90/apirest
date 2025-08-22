<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleFactura extends Model
{
    use HasFactory;
    protected $table = 'beca';
    public $incrementing = false;
    protected $fillable = [ 'idBeca','descripcion','aplicaInscripcion','aplicaColegiatura','fechaAlta','fechaModificacion'];
    public $timestamps = false;
}
