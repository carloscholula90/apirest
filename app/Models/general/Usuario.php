<?php

namespace App\Models\general;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model{

    use HasFactory;
    protected $table = 'usuario';
    protected $primaryKey = ['uid', 'secuencia'];
    public $incrementing = false;
   

    public function persona(){
        return $this->belongsTo(Persona::class);
    }

    public function find(array $ids, $columns = ['*']){
        $query = $this->newQuery();

        // Construir la consulta con las llaves primarias compuestas
        $query->where('uid', $ids['uid'])
              ->where('secuencia', $ids['secuencia']);

        return $query->first($columns);
    }
}

