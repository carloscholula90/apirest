<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\CodigoPostal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CodigoPostalController extends Controller{

    
    public function show($idCodigoPostal){
        try {
            $codigoPostal =  DB::table('codigoPostal as cp')
                                        ->select(
                                                'cp.descripcion as colonia',
                                                'cp.cp',
                                                'cp.idCp',
                                                'cp.idPais',
                                                'cp.idEstado',
                                                'cp.idCiudad',  
                                                'cp.idAsentamiento',
                                                'pais.descripcion as pais',
                                                'estado.descripcion as estado',
                                                'ciudad.descripcion as ciudad',
                                                'asentamiento.descripcion as asentamiento')
                                                ->leftJoin('estado', function($join) {
                                                            $join->on('estado.idEstado', '=', 'cp.idEstado')
                                                                 ->on('estado.idPais', '=', 'cp.idPais');
                                                            })
                                                ->leftJoin('ciudad', function($join) {
                                                                $join->on('ciudad.idEstado', '=', 'cp.idEstado')
                                                                     ->on('ciudad.idPais', '=', 'ciudad.idPais')
                                                                     ->on('cp.idCiudad', '=', 'cp.idCiudad');
                                                                })
                                                ->leftJoin('pais', 'pais.idPais', '=', 'cp.idPais')
                                                ->leftJoin('asentamiento', 'asentamiento.idAsentamiento', '=', 'cp.idAsentamiento')
                                                ->orderBy('cp.descripcion', 'asc')   
                                                ->where('cp.cp', '=',$idCodigoPostal)
                                                ->get();


            return $this->returnData('codigospostales',$codigoPostal,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('CodigoPostal no encontrado',404,null); 
        }
    }
}
