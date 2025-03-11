<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alergia extends Model
{
    use HasFactory;
    protected $table = 'alergia';
    protected $primaryKey = null;    
    protected $keyType = 'int';
    public $timestamps = false;
    public $incrementing = true;
  
    protected $fillable = [
                        'uid',
                        'consecutivo',
                        'alergia'
    ];
}
