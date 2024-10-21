<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escolaridad extends Model
{
    use HasFactory;
    protected $table = 'escolaridad';
    protected $primaryKey = 'idEscolaridad';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['idEscolaridad','descripcion'];
    public $timestamps = false;
}
