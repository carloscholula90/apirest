<?php

namespace App\Models\tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsoCFDI extends Model
{
    use HasFactory;
    protected $table = 'usosCFDI';
    protected $primaryKey = null;    
    protected $keyType = null;
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
                        'idUsoCFDI',
                        'descripcion',
                        'fisica',
                        'moral'
    ];
}
