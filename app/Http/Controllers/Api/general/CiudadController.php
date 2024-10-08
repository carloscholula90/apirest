<?php

namespace App\Http\Controllers\Api\general; 
 
use App\Http\Controllers\Controller;
use App\Models\general\Ciudad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class CiudadController extends Controller
{
public function index(){     
        
    $ciudades = Ciudad::join('pais', 'ciudad.idPais', '=', 'pais.idPais')
                        ->join('estado', 'ciudad.idEstado', '=', 'estado.idEstado')
                        ->select( 'pais.idPais',
                                'pais.descripcion as paisDescripcion',
                                'estado.idEstado',
                                'estado.descripcion as estadoDescripcion',
                                'ciudad.idCiudad',
                                'ciudad.descripcion as ciudadDescripcion'
                                )
                               ->get();

   $data = [
       'estados' => $ciudades,
       'status' => 200
   ];

   return response()->json($data, 200);
}

public function store(Request $request)
{
   
   $validator = Validator::make($request->all(), [
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

   $maxId = Ciudad::max('idEstado');
   $newId = $maxId ? $maxId + 1 : 1;
   $ciudades = Ciudad::create([
       'idEstado' => $newId,
       'descripcion' => $request->descripcion
   ]);

   if (!$ciudades) {
       $data = [
           'message' => 'Error al crear el ',
           'status' => 500
       ];
       return response()->json($data, 500);
   }
   $ciudades = Ciudad::findOrFail($newId);

   $data = [
       'estado' => $ciudades,
       'status' => 201
   ];

   return response()->json($data, 201);

}

public function show($idPais,$idEstado,$idCiudad){
   try {
       // Busca el  por ID y lanza una excepción si no se encuentra
       $ciudades = Ciudad::join('pais', 'ciudad.idPais', '=', 'pais.idPais')
                            ->join('estado','ciudad.idEstado','=','estado.idEstado')
                               ->select( 'pais.idPais',
                                       'pais.descripcion as paisDescripcion',
                                       'estado.idEstado',
                                       'estado.descripcion as estadoDescripcion',
                                       'ciudad.idCiudad',
                                       'ciudad.descripcion as ciudadDescripcion'
                               )
                               ->where('ciudad.idPais', '=', $idPais)
                               ->where('ciudad.idEstado', '=', $idEstado)
                               ->where('ciudad.idCiudad', '=', $idCiudad)
                               ->get();

       // Retorna el  con estado 200
       $data = [
           'estado' => $ciudades,
           'status' => 200
       ];
       return response()->json($data, 200);
   } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
       // Si el  no se encuentra, retorna un mensaje de error con estado 404
       $data = [
           'message' => 'Estado no encontrado',
           'status' => 404
       ];
       return response()->json($data, 404);
   }
}

public function destroy($idPais,$idEstado,$idCiudad){
   $ciudades = Ciudad::find($idPais,$idEstado,$idCiudad);

   if (!$ciudades) {
       $data = [
           'message' => 'Ciudad no encontrado',
           'status' => 404
       ];
       return response()->json($data, 404);
   }
   
   $ciudades->delete();

   $data = [
       'message' => 'Ciudad eliminado',
       'status' => 200
   ];

   return response()->json($data, 200);
}

public function update(Request $request){

   $ciudades = Ciudad::find($request->idPais,$request->idEstado,$request->idCiudad);
   if (!$ciudades) {
       $data = [
                    'message' => 'Ciudad no encontrado',
                    'status' => 404
       ];
       return response()->json($data, 404);
   }

   $validator = Validator::make($request->all(), [
                                            'idCiudad' => 'required|numeric|max:255',
                                            'idEstado' => 'required|numeric|max:255',
                                            'idPais' => 'required|numeric|max:255',
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

   $ciudades->idEstado = $request->idEstado;
   $ciudades->idPais =   $request->idPais;
   $ciudades->idPais =   $request->idCiudad;
   $ciudades->descripcion = $request->descripcion;
   $ciudades->save();

   $data = [
           'message' => 'Ciudad actualizado',
           'ciudad' => $ciudades,
           'status' => 200
   ];
   return response()->json($data, 200);
}
}