<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    use HasFactory;
    protected $table = 'turno';
    protected $primaryKey = 'idTurno';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idTurno',
                        'descripcion',
                        'letra',
                        'parciales'
    ];

    public function detallesTurno()
    {
        return $this->hasMany(DetalleTurno::class, 'idTurno', 'idTurno');
    }
}
