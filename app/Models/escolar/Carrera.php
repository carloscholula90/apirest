<?php

namespace App\Models\escolar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    use HasFactory;
    protected $table = 'carrera';
    protected $primaryKey = 'idCarrera';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idCarrera',
                        'descripcion',
                        'letra',
                        'diaInicioCargo',
                        'diaInicioRecargo'
    ];
}
