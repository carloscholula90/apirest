<?php

namespace App\Models\general;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

    class DetMedio extends Model{

    use HasFactory;
    
    
    protected $table = 'detMedio';
    protected $primaryKey = ['uid', 'consecutivo'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['idMedio', 'idRol','fechaAlta','fechaModificacion','secuencia', 'uid'];
    public $timestamps = false;

    public static function findById($uid, $consecutivo){
        return self::where('uid', $uid)->where('consecutivo', $consecutivo)->first();
    }
}
