<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Idiomas extends Model
{
    use HasFactory;
    protected $table = 'idiomas';
    protected $primaryKey = 'idIdioma';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['idIdioma','descripcion'];
    public $timestamps = false;
}
