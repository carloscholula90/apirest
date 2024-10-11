<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class avisosPrivacidad extends Model
{
    use HasFactory;
    protected $table = 'avisosPrivacidad';
    protected $primaryKey = 'idAviso';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [ 'idAviso','descripcion','activo','archivo'];
    public $timestamps = false;
}
