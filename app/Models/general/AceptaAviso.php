<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class avisosPrivacidad extends Model
{
    use HasFactory;
    protected $table = 'aceptaAvisosPriv';
    protected $primaryKey = ['idAviso', 'uid'];
    public $incrementing = false;
    protected $keyType = 'int';
    protected $fillable = [ 'idAviso','uid','fechaAcepta'];
    public $timestamps = false;
}
