<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class puestos extends Model
{
    use HasFactory;
    protected $table = 'puestos';
    protected $primaryKey = 'idPuesto';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [ 'idPuesto','descripcion'];
    public $timestamps = false;
}
