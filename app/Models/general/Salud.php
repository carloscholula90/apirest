<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class salud extends Model
{
    protected $table = 'salud';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [ 'uid',
                            'secuencia',
                            'enfermedad',
                            'medico',
                            'telefono'
    ];
}
