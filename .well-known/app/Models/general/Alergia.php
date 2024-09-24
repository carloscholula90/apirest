<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alergia extends Model
{
    use HasFactory;

    protected $table = 'alergia';

    protected $fillable = [
        'uid',
        'consecutivo',
        'alergia'
    ];

}
