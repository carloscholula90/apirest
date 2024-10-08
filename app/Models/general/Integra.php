<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class integra extends Model
{
    use HasFactory;
    protected $table = 'integra';
    protected $primaryKey = 'secuencia';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [ 'uid','secuencia','idRol','activo','fechainicio','fechabaja','matriculae'];
    public $timestamps = false;

}
