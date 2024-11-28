<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Periodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PeriodoController extends Controller{

    public function index(){       
        $periodos = Periodo::all();
        return $this->returnData('periodos',$periodos,200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                                        'idNivel' =>'required|numeric|max:255',
                                        'idPeriodo' =>'required|numeric|max:255',
                                        'descripcion' => 'required|max:255',
                                        'activo' =>'required|numeric|max:255',
                                        'inscripciones' =>'required|numeric|max:255',
                                        'fechaInicio' =>'required|date',
                                        'fechaTermino' =>'required|date',
                                        'inmediato' =>'required|numeric|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
    
        try {
            $periodos = Periodo::create([
                            'idPeriodo' => $request->idPeriodo,
                            'idNivel' => $request->idNivel,
                            'descripcion' => strtoupper(trim($request->descripcion)),
                            'activo' => $request->activo,
                            'inscripciones' => $request->inscripciones,
                            'fechaInicio' => $request->fechaInicio,
                            'fechaTermino' => $request->fechaTermino,
                            'inmediato' => $request->inmediato
                        ]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') 
                return $this->returnEstatus('El Periodo ya se encuentra dado de alta',400,null);
            return $this->returnEstatus('Error al insertar el Periodo',400,null);
        }  

        if (!$periodos) 
            return $this->returnEstatus('Error al crear el Periodo',500,null); 
        return $this->returnData('$periodos',$periodos,201);   
    }

    public function show($idPeriodo,$idNivel){
        try {
            $periodos = Periodo::find($idNivel, $idPeriodo);
            return $this->returnData('$periodos',$periodos,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Periodo no encontrado',404,null); 
        }
    }
    
    public function destroy($idPeriodo,$idNivel){
        $periodos = Periodo::find($idNivel, $idPeriodo);
          
        if (!$periodos) 
            return $this->returnEstatus('Periodo no encontrado',404,null);   

        $deletedRows = Periodo::where('idNivel', $idNivel)
                       ->where('idPeriodo', $idPeriodo)
                       ->delete();

        return $this->returnEstatus('Periodo eliminado',200,null); 
    }

    public function update(Request $request, $idPeriodo, $idNivel){

        $periodos = Periodo::find($idNivel, $idPeriodo);
        
        if (!$periodos)      
            return $this->returnEstatus('Periodo no encontrado periodo ',404,null);             

        $validator = Validator::make($request->all(), [  
                                'descripcion' => 'required|max:255',
                                'activo' =>'required|numeric|max:255',
                                'inscripciones' =>'required|numeric|max:255',
                                'fechaInicio' =>'required|date',
                                'fechaTermino' =>'required|date',
                                'inmediato' =>'required|numeric|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $periodos->idPeriodo = $idPeriodo;
        $periodos->idNivel = $idNivel;    
        $periodos->descripcion = strtoupper(trim($request->descripcion));
        $periodos->activo = $request->activo;
        $periodos->inscripciones = $request->inscripciones;
        $periodos->fechaInicio = $request->fechaInicio;
        $periodos->fechaTermino = $request->fechaTermino;
        $periodos->inmediato = $request->inmediato;

        $periodos->save();
        return $this->returnData('periodo',$periodos,200);
    }

    public function updatePartial(Request $request, $idPeriodo,$idNivel){

        $periodos = Periodo::find($idNivel, $idPeriodo);
        
        if (!$periodos) 
            return $this->returnEstatus('Periodo no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idNivel' =>'required|numeric|max:255',
                                    'idPeriodo' =>'required|numeric|max:255',
                                    'descripcion' => 'required|max:255',
                                    'activo' =>'required|numeric|max:255',
                                    'inscripciones' =>'required|numeric|max:255',
                                    'fechaInicio' =>'required|date',
                                    'fechaTermino' =>'required|date',
                                    'inmediato' =>'required|numeric|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idPeriodo')) 
            $periodos->idPeriodo = $request->idPeriodo;        

        if ($request->has('descripcion')) 
            $periodos->descripcion = strtoupper(trim($request->descripcion));        

        $periodos->save();
        return $this->returnEstatus('Periodo actualizado',200,null);    
    }
}
