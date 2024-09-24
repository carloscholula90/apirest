<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asentamiento extends Model
{
    use HasFactory;

    protected $table = 'asentamiento';

    protected $fillable = [
        'idAsentamiento',
        'descripcion'
    ];

}
