<?php

namespace App\Models\escolar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class nivel extends Model
{
    use HasFactory;
    protected $table = 'nivel';
    protected $primaryKey = 'idNivel';    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idNivel',
                        'descripcion'
    ];
}
