<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoContacto extends Model
{
    use HasFactory;
    protected $table = 'tipoContacto';
    protected $primaryKey = 'idTipoContacto';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [ 'idTipoContacto','descripcion'];
    public $timestamps = false;
}
