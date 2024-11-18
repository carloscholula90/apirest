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
                            $periodos->idPeriodo = $request->idPeriodo,
                            $periodos->idNivel => $request->idNivel,
                            $periodos->descripcion => strtoupper(trim($request->descripcion)),
                            $periodos->activo =>$request->activo,
                            $periodos->inscripciones =>$request->inscripciones,
                            $periodos->fechaInicio =>$request->fechaInicio,
                            $periodos->fechaTermino =>$request->fechaTermino,
                            $periodos->inmediato =>$request->inmediato
                        ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El Periodo ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el Periodo',400,null);
        }

        if (!$periodos) 
            return $this->returnEstatus('Error al crear el Periodo',500,null); 
        return $this->returnData('$periodos',$periodos,201);   
    }

    public function show($idPeriodo,$idNivel){
        try {
            $periodos = Periodo::find($idPeriodo,$idNivel);
            return $this->returnData('$periodos',$periodos,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Periodo no encontrado',404,null); 
        }
    }
    
    public function destroy($idPeriodo,$idNivel){
        $periodos = Periodo::find($idPeriodo,$idNivel);

        if (!$periodos) 
            return $this->returnEstatus('Periodo no encontrado',404,null);             
        
            $periodos->delete();
        return $this->returnEstatus('Periodo eliminado',200,null); 
    }

    public function update(Request $request, $idPeriodo, $idNivel){

        $periodos = Periodo::find($idPeriodo,$idNivel);
        
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
            
        $periodos->idPeriodo = $request->idPeriodo;
        $periodos->idNivel = $request->idNivel;  
        $periodos->descripcion = strtoupper(trim($request->descripcion));
        $periodos->activo = $request->activo;
        $periodos->inscripciones = $request->inscripciones;
        $periodos->fechaInicio = $request->fechaInicio;
        $periodos->fechaTermino = $request->fechaTermino;
        $periodos->inmediato = $request->inmediato;

        $periodos->save();
        return $this->returnData('periodo',$periodo,200);
    }

    public function updatePartial(Request $request, $idPeriodo,$idNivel){

        $periodos = Periodo::find($idPeriodo,$idNivel);
        
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
