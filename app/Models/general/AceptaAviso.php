<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AceptaAviso extends Model
{
    use HasFactory;
    protected $table = 'aceptaAvisosPriv';
    protected $primaryKey = ['idAviso', 'uid'];
    public $incrementing = false;
    protected $fillable = [ 'idAviso','uid','fechaAcepta','ip'];
    public $timestamps = false;
}
