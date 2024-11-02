<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;
    protected $table = 'documento';
    protected $primaryKey = 'idDocumento';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['idDocumento','descripcion'];
    public $timestamps = false;
}