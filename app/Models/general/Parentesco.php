<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class parentesco extends Model
{
    use HasFactory;
    protected $table = 'parentesco';
    protected $primaryKey = 'idParentesco';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [ 'idParentesco','descripcion'];
    public $timestamps = false;

}
