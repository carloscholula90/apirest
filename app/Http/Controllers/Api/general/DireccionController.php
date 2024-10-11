<?php

namespace App\Http\Controllers\Api\general;

use App\Http\Controllers\Controller;
use App\Models\general\Direccion;   
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DireccionController extends Controller
{
    public function index(){    
        $direcciones = Direccion::join('pais', 'direcciones.idPais', '=', 'pais.idPais')
        ->join('estado', function($join) {
            // Eliminar el operador "->" antes de "on()"
            $join->on('direcciones.idEstado', '=', 'estado.idEstado')
                 ->on('direcciones.idPais', '=', 'estado.idPais'); 
        })
        ->join('ciudad', function($join) {
            $join->on('direcciones.idEstado', '=', 'ciudad.idEstado')
                 ->on('direcciones.idPais', '=', 'ciudad.idPais')
                 ->on('direcciones.idCiudad', '=', 'ciudad.idCiudad'); 
        })
        ->join('parentesco', 'direcciones.idParentesco', '=', 'parentesco.idParentesco')
        ->join('codigoPostal', function($join) {
            $join->on('direcciones.idPais', '=', 'codigoPostal.idPais')
                 ->on('direcciones.idEstado', '=', 'codigoPostal.idEstado')
                 ->on('direcciones.idCiudad', '=', 'codigoPostal.idCiudad')
                 ->on('direcciones.idCp', '=', 'codigoPostal.idCp'); 
        })
        ->join('asentamiento', 'codigoPostal.idAsentamiento', '=', 'asentamiento.idAsentamiento')
        ->select(
                    'direcciones.uid',
                    'pais.idPais',
                    'pais.descripcion as paisDescripcion',
                    'estado.idEstado',
                    'estado.descripcion as estadoDescripcion',
                    'ciudad.idCiudad',
                    'ciudad.descripcion as ciudadDescripcion',
                    'direcciones.noExterior',
                    'direcciones.noInterior',
                    'codigoPostal.cp',
                    'codigoPostal.descripcion',
                    'direcciones.calle',
                    'direcciones.consecutivo',
                    'asentamiento.descripcion as asentamientoDescripcion'
                )
                ->get();  
        return $this->returnData('direcciones',$direcciones,200);
   }

   public function show($uid,$idParentesco){
    $direcciones = Direccion::join('pais', 'direcciones.idPais', '=', 'pais.idPais')
                                ->join('estado', function($join) {
                                       $join->on('direcciones.idEstado', '=', 'estado.idEstado')
                                        ->on('direcciones.idPais', '=', 'estado.idPais'); 
                                })
                                ->join('ciudad', function($join) {
                                    $join->on('direcciones.idEstado', '=', 'ciudad.idEstado')
                                        ->on('direcciones.idPais', '=', 'ciudad.idPais')
                                        ->on('direcciones.idCiudad', '=', 'ciudad.idCiudad'); 
                                })
                                ->join('parentesco', 'direcciones.idParentesco', '=', 'parentesco.idParentesco')
                                ->join('codigoPostal', function($join) {
                                    $join->on('direcciones.idPais', '=', 'codigoPostal.idPais')
                                        ->on('direcciones.idEstado', '=', 'codigoPostal.idEstado')
                                        ->on('direcciones.idCiudad', '=', 'codigoPostal.idCiudad')
                                        ->on('direcciones.idCp', '=', 'codigoPostal.idCp'); 
                                })
                                ->join('asentamiento', 'codigoPostal.idAsentamiento', '=', 'asentamiento.idAsentamiento')
                                ->select(
                                    'direcciones.uid',
                                    'pais.idPais',
                                    'pais.descripcion as paisDescripcion',
                                    'estado.idEstado',
                                    'estado.descripcion as estadoDescripcion',
                                    'ciudad.idCiudad',
                                    'ciudad.descripcion as ciudadDescripcion',
                                    'direcciones.noExterior',
                                    'direcciones.noInterior',
                                    'codigoPostal.cp',
                                    'codigoPostal.descripcion',
                                    'direcciones.calle',
                                    'direcciones.consecutivo',
                                    'asentamiento.descripcion as asentamientoDescripcion'
                                )
                                    ->where('parentesco.idParentesco', '=', $idParentesco)
                                    ->where('direcciones.uid', '=', $uid)          
                        ->get();
            
        return $this->returnData('direcciones',$direcciones,200);
   }
   
   public function destroy($uid,$consecutivo){            
            $direcciones = Direccion::find($uid, $consecutivo);
                            
            if (!$direcciones)     
                return $this->returnEstatus('Dirección no encontrada',400,null); 
            
            $direcciones->delete();
            return $this->returnEstatus('Dirección eliminada',200,null); 
   }
  
   public function update(Request $request){
             
   }

   public function store(Request $request)
   {
      
   }
  
}