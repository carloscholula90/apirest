<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoExamen extends Model
{
    use HasFactory;
    protected $table = 'tipoExamen';
    protected $primaryKey = 'idTipoExamen';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idTipoExamen',
                        'descripcion'
    ];
}
