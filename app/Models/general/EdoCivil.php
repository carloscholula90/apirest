<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class edoCivil extends Model
{
    use HasFactory;
    protected $table = 'edoCivil';
    protected $primaryKey = 'idEdoCivil';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [ 'idEdoCivil','descripcion'];
    public $timestamps = false;
}
