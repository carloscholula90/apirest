<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoBaja extends Model
{
    use HasFactory;

    protected $table = 'tipoBaja';
    protected $primaryKey = 'idTipoBaja';
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'idTipoBaja',
        'descripcion',
    ];
}
