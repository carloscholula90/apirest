<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model{
    
    protected $table = 'horarios';
    protected $primaryKey = 'grupoSec';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'grupoSec',
        'horalIni','horalFin',
        'horamIni','horamFin',
        'horammIni','horammFin',
        'horajIni','horajFin',
        'horavIni','horavFin',
        'horasIni','horasFin',
        'horadIni','horadFin'
    ];
}