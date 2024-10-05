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
                            ->join('estado', 'direcciones.idEstado', '=', 'estado.idEstado')
                            ->join('ciudad', 'direcciones.idCiudad', '=', 'ciudad.idCiudad')
                            ->join('parentesco', 'direcciones.idParentesco', '=', 'parentesco.idParentesco')
                            ->join('codigoPostal', function($join) {
                                                   $join->on('direcciones.idPais', '=', 'codigoPostal.idPais')
                                                        ->on('direcciones.idEstado', '=', 'codigoPostal.idEstado')
                                                        ->on('direcciones.idCiudad', '=', 'codigoPostal.idCiudad')
                                                        ->on('direcciones.idCp', '=', 'codigoPostal.idCp'); 
                                                    }
                                    )
                            ->join('asentamiento', 'codigoPostal.idAsentamiento', '=', 'asentamiento.idAsentamiento') 
                            ->select('pais.idPais',
                                     'pais.descripcion as paisDescripcion',
                                     'estado.idEstado',
                                     'estado.descripcion as estadoDescripcion',
                                     'ciudad.idCiudad',
                                     'ciudad.descripcion as ciudadDescripcion',
                                     'direcciones.noExterior',
                                     'direcciones.noInterior',   
                                     'codigoPostal.cp',   
                                     'codigoPostal.descripcion',
                                     'asentamiento.descripcion as asentamientoDescripcion'
                                   )   
                                   ->get();
       $data = [
                'direcciones' => $direcciones,
                'status' => 200
       ];
       return response()->json($data, 200);
   }

   public function store(Request $request)
   {
   
   }

   public function show($uid,$idParentesco){
                    $direcciones = Direccion::join('pais', 'direcciones.idPais', '=', 'pais.idPais')
                                    ->join('estado', 'direcciones.idEstado', '=', 'estado.idEstado')
                                    ->join('ciudad', 'direcciones.idCiudad', '=', 'ciudad.idCiudad')
                                    ->join('parentesco', 'direcciones.idParentesco', '=', 'parentesco.idParentesco')
                                    ->join('codigoPostal', function($join) {
                                                        $join->on('direcciones.idPais', '=', 'codigoPostal.idPais')
                                                             ->on('direcciones.idEstado', '=', 'codigoPostal.idEstado')
                                                             ->on('direcciones.idCiudad', '=', 'codigoPostal.idCiudad')
                                                             ->on('direcciones.idCp', '=', 'codigoPostal.idCp'); 
                                            })
                                    ->join('asentamiento', 'codigoPostal.idAsentamiento', '=', 'asentamiento.idAsentamiento') 
                                    ->select('pais.idPais',
                                            'pais.descripcion as paisDescripcion',
                                            'estado.idEstado',
                                            'estado.descripcion as estadoDescripcion',
                                            'ciudad.idCiudad',
                                            'ciudad.descripcion as ciudadDescripcion',
                                            'direcciones.noExterior',
                                            'direcciones.noInterior',   
                                            'codigoPostal.cp',   
                                            'codigoPostal.descripcion',
                                            'asentamiento.descripcion as asentamientoDescripcion'
                                        ) 
                                    ->where('parentesco.idParentesco', '=', $idParentesco)
                                    ->where('direcciones.uid', '=', $uid)        
                        ->get();
        $data = [
                'direcciones' => $direcciones,
                'status' => 200
        ];
        return response()->json($data, 200);
   }
   
   public function destroy($idPais,$idEstado){
       $estados = Estado::find($idPais,$idEstado);

       if (!$estados) {
           $data = [
               'message' => 'Estado no encontrado',
               'status' => 404
           ];
           return response()->json($data, 404);
       }
       
       $estados->delete();

       $data = [
           'message' => 'Estado eliminado',
           'status' => 200
       ];

       return response()->json($data, 200);
   }

   public function update(Request $request){
     
       $estados = Estado::find($request->idPais,$request->idEstado);
       if (!$estados) {
           $data = [
               'message' => 'Estado no encontrado',
               'status' => 404
           ];
           return response()->json($data, 404);
       }
      
       $validator = Validator::make($request->all(), [
                               'idPais' => 'required|numeric|max:255',
                               'idEstado' => 'required|numeric|max:255',                                
                               'descripcion' => 'required|max:255'
       ]);   

       if ($validator->fails()) {
           $data = [
                   'message' => 'Error en la validación de los datos',
                   'errors' => $validator->errors(),
                   'status' => 400
           ];
           return response()->json($data, 400);
       }
       \Log::info('Datos de usuario procesados 1');

       $estados = Estado::where('idPais', $request->idPais)
                ->where('idEstado', $request->idEstado)
                ->first();
                \Log::info('Datos de usuario procesados 2');

       if ($estados) {
           $estados->descripcion = $request->descripcion;
           $estados->save();
           $data = [
               'message' => 'Estado actualizado',
               'estados' => $estados,
               'status' => 200
           ];
       return response()->json($data, 200);
       } else {
           return response()->json(['error' => 'Estado no encontrado'], 404);
       }   
      
      
   }
}