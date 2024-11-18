<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    use HasFactory;
    protected $table = 'periodo';
    protected $primaryKey = null;   
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = null;  
    protected $fillable = [ 'idNivel','idPeriodo','descripcion','activo','inscripciones',
                            'fechaInicio','fechaTermino','inmediato'];

    
    public static function find($idNivel, $idPeriodo) {
        return static::where('idNivel', $idNivel)
                            ->where('idPeriodo', $idPeriodo)
                            ->first();
    }
}
