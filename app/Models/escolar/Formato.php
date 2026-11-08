<?php

namespace App\Models\escolar;

use Illuminate\Database\Eloquent\Model;

class Formato extends Model
{
    protected $table = 'formato';
    protected $primaryKey = 'idFormato';
    public $incrementing = false;
    public $timestamps = false;
}
