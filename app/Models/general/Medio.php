<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class medio extends Model
{
    use HasFactory;
    protected $table = 'medio';
    protected $primaryKey = 'idMedio';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [ 'idMedio','descripcion'];
    public $timestamps = false;

}
