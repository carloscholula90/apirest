<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoContrato extends Model
{
    use HasFactory;
    protected $table = 'tipoContrato';
    protected $primaryKey = 'idTipoContrato';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [ 'idTipoContrato','descripcion'];
    public $timestamps = false;
}
