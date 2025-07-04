<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;  
use Illuminate\Database\Eloquent\Model;
   
class DiasFestivos extends Model    
{
    use HasFactory;  
    protected $table = 'diasFestivos';    
    protected $fillable = ['fechaFestivo', 'descripcion'];
    public $timestamps = false; 
    public $incrementing = false;
    protected $primaryKey = null;
}
