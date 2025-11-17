<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BecaAlumno extends Model
{
    use HasFactory;
    protected $table = 'becaAlumno';
    public $incrementing = false;
    protected $fillable = [ 'idNivel','idPeriodo','idBeca','uid','secuencia','importeInsc','importeCole','fechaAlta','fechaModificacion'];
    public $timestamps = false;
}
