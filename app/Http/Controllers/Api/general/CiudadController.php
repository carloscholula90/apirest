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
    return $this->returnData('ciudades',$ciudades,200);
}

public function store(Request $request)
{
   
   $validator = Validator::make($request->all(), [
                            'idPais' =>'required|numeric|max:255',
                            'idEstado' =>'required|numeric|max:255',
                            'descripcion' => 'required|max:255'
   ]);

   if ($validator->fails()) 
    return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

   $maxId = Ciudad::where('idPais',$request->idPais)
                    ->where('idEstado',$request->idEstado)
                    ->max('idCiudad');
   
   $newId = $maxId ? $maxId + 1 : 1;
   $ciudades = Ciudad::create([
                            'idCiudad' => $newId,
                            'idPais' =>$request->idPais,
                            'idEstado'=>$request->idEstado,
                            'descripcion' =>strtoupper(trim( $request->descripcion))
   ]);
  
   if (!$ciudades) 
        return $this->returnEstatus('Error al crear la ciudad',404,null); 
    return $this->returnEstatus('Ciudad generada con éxito '.$newId,200,null);

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
       return $this->returnData('ciudades',$ciudades,200);
   } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return $this->returnEstatus('Ciudad no encontrada',404,null); 
   }
}

public function destroy($idPais,$idEstado,$idCiudad){
   $ciudades = Ciudad::where('idPais',$idPais)
                        ->where('idEstado',$idEstado)
                        ->where('idCiudad',$idCiudad);
   
   if (!$ciudades)
    return $this->returnEstatus('Ciudad no encontrada',404,null); 
   $ciudades->delete();
   return $this->returnEstatus('Ciudad eliminada',404,null); 
}

public function update(Request $request){

   $ciudades = Ciudad::find($request->idPais,$request->idEstado,$request->idCiudad);
   if (!$ciudades) 
    return $this->returnEstatus('Ciudad no encontrada',404,null);

   $validator = Validator::make($request->all(), [
                                            'idCiudad' => 'required|numeric|max:255',
                                            'idEstado' => 'required|numeric|max:255',
                                            'idPais' => 'required|numeric|max:255',
                                            'descripcion' => 'required|max:255'
   ]);   

   if ($validator->fails()) 
    return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

   $ciudades->idEstado = $request->idEstado;
   $ciudades->idPais =   $request->idPais;
   $ciudades->idCiudad =   $request->idCiudad;
   $ciudades->descripcion = strtoupper(trim($request->descripcion));
   $ciudades->save();
   return $this->returnEstatus('Ciudad actualizada',200,null); 
}
}