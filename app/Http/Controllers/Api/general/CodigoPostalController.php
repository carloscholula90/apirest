<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use App\Models\general\CodigoPostal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CodigoPostalController extends Controller{

    public function index(){       
        
    }

    public function store(Request $request)
    {

          
    }

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
    
    public function destroy($idCodigoPostal){
        $codigoPostal = CodigoPostal::find($idCodigoPostal);

        if (!$codigoPostal) 
            return $this->returnEstatus('CodigoPostal no encontrado',404,null);             
        
            $codigoPostal->delete();
        return $this->returnEstatus('CodigoPostal eliminado',200,null); 
    }

    
    public function update(Request $request, $idCodigoPostal){

        $codigoPostal = CodigoPostal::select('*')
                                        ->where('uid',$idCodigoPostal)
                                        ->get();    
        
        if (!$codigoPostal) 
            return $this->returnEstatus('CodigoPostal no encontrado',404,null);             

            $validator = Validator::make($request->all(), [
                                            'idPais' => 'required|numeric|max:255',
                                            'idEstado' => 'required|numeric|max:255',
                                            'idCiudad' => 'required|numeric|max:255',
                                            'idCp' => 'required|numeric|max:255',
                                            'cp' => 'required|numeric|max:255',
                                            'descripcion' => 'required|max:255',
                                            'idAsentamiento' => 'required|numeric|max:255'
            ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idPais')) 
            $codigoPostal->idPais = $request->idPais;        

        if ($request->has('idEstado')) 
            $codigoPostal->idEstado = strtoupper(trim($request->idEstado)); 
        
        if ($request->has('idCiudad')) 
            $codigoPostal->idCiudad = strtoupper(trim($request->idCiudad)); 
        
        if ($request->has('idCp')) 
            $codigoPostal->idCp = strtoupper(trim($request->idCp)); 

        if ($request->has('descripcion')) 
            $codigoPostal->descripcion = strtoupper(trim($request->descripcion)); 

        if ($request->has('idAsentamiento')) 
            $codigoPostal->idAsentamiento = strtoupper(trim($request->idAsentamiento));

        $codigoPostal->save();
        return $this->returnEstatus('CodigoPostal actualizado',200,null);    
    }
     
    public function generaReporte()
    {
      
    } 

    public function exportaExcel() {
           
   }   
}
