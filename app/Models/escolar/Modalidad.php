<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class modalidad extends Model
{
    use HasFactory;
    protected $table = 'modalidad';
    protected $primaryKey = 'idModalidad';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idModalidad',
                        'descripcion'
    ];
}
