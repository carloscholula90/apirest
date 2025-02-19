<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Carrera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CarreraController extends Controller
{
    public function index()
    {
        $carreras = Carrera::all();
        return $this->returnData('carreras',$carreras,200);        
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                        'descripcion' => 'required|max:255',
                        'letra' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
        
         $maxIdCarrera = Carrera::max('idCarrera');
        $newIdCarrera = $maxIdCarrera ? $maxIdCarrera + 1 : 1;

        $carreras = Carrera::create([
                                    'idCarrera' => $newIdCarrera,
                                    'descripcion' => strtoupper(trim($request->descripcion)),
                                    'letra'=> strtoupper(trim($request->letra)),
                                    'diaInicioCargo'=>$request->diaInicioCargo,
                                    'diaInicioRecargo'=>$request->diaInicioRecargo
        ]);

        if (!$carreras) 
            return $this->returnEstatus('Error al crear la carrera',500,null);         
        
        $carreras = Carrera::find($newIdCarrera);
        return $this->returnData('carreras',$carreras,200);   
    }

    public function show($idCarrera)
    {
        $carreras = Carrera::find($idCarrera);

        if (!$carreras) 
            return $this->returnEstatus('Carrera no encontrada',400,null);  

        return $this->returnData('carreras',$carreras,200);
    }

    public function destroy($idCarrera)
    {
        $carreras = Carrera::find($idCarrera);

        if (!$carreras) 
            return $this->returnEstatus('Carrera no encontrada',400,null);  
        
        $carreras->delete();
        return $this->returnEstatus('Carrera eliminada',200,null);        
    }

    

    public function updatePartial(Request $request, $idCarrera)
    {
        if ($idCarrera==null) 
            return $this->returnEstatus('Agregue un Id de carrera válido',400,null); 
               
        $carreras = Carrera::find($idCarrera);

        if (!$carreras) 
            return $this->returnEstatus('Carrera no encontrado',400,null); 
        
        $carreras->idCarrera = $idCarrera;        

        if ($request->has('descripcion')) 
            $carreras->descripcion = $request->descripcion;
        

        if ($request->has('letra')) 
            $carreras->letra = $request->letra;
        

        if ($request->has('diaInicioCargo')) 
            $carreras->diaInicioCargo = $request->diaInicioCargo;
        

        if ($request->has('diaInicioRecargo')) 
            $carreras->diaInicioRecargo = $request->diaInicioRecargo;
        

        $carreras->save();
        return $this->returnEstatus('Carrera actualizada',200,null); 
    }

    public function generaReporte(){
        return $this->imprimeCtl('carrera','carrera');
    } 
       
    public function exportaExcel() {  
        return $this->exportaXLS('carrera','idCarrera', ['CLAVE','DESCRIPCIÓN']);     
    }
}
