<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pais extends Model
{
    use HasFactory;
    protected $table = 'pais';
    protected $primaryKey = 'idPais';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [ 'idPais','descripcion','nacionalidad'];
    public $timestamps = false;
}
