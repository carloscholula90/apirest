<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tipoExamen extends Model
{
    use HasFactory;
    protected $table = 'tipoExamen';
    protected $primaryKey = 'idExamen';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['idExamen','descripcion'];
    public $timestamps = false;
}
