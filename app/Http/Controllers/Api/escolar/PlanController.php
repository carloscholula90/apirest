<?php

namespace App\Http\Controllers\Api\escolar;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\escolar\Plan;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;

class PlanController extends Controller{    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $result = DB::table('plan')
                         ->join('nivel', 'nivel.idNivel', '=', 'plan.idNivel')
                         ->join('detasignatura', function ($join) {
                            $join->on('detasignatura.idPlan', '=', 'plan.idPlan')
                                 ->on('detasignatura.idCarrera', '=', 'plan.idCarrera');
                        })  
                         ->join('asignatura', 'asignatura.idAsignatura', '=', 'detasignatura.idAsignatura')
                         ->join('modalidad', 'modalidad.idModalidad', '=', 'plan.idModalidad')
                         ->join('carrera', function ($join) {
                                        $join->on('carrera.idCarrera', '=', 'plan.idCarrera')
                                             ->on('carrera.idNivel', '=', 'plan.idNivel');
                        })  
                                           
                        ->select(
                                    'plan.descripcion',
                                    'plan.rvoe',
                                    'plan.fechainicio',
                                    'plan.semestres',
                                    'plan.vigente',
                                    'plan.decimales',
                                    'plan.minAprobatoria',
                                    'plan.estatal',
                                    'plan.minAprobatoria',
                                    'plan.grado',
                                    'plan.idCarrera',
                                    'plan.idNivel',
                                    'plan.idModalidad',
                                    'carrera.descripcion as carrera',
                                    'modalidad.descripcion as modalidad',
                                    'nivel.descripcion as nivel',
                                    'asignatura.descripcion as asignatura',
                                    'asignatura.idAsignatura',
                                    'carrera.idCarrera',
                                    'detasignatura.idPlan',
                                    'detasignatura.seriacion',
                                    'detasignatura.ordenk',
                                    'detasignatura.ordenc',
                                    'detasignatura.condocente',
                                    'detasignatura.independientes',
                                    'detasignatura.creditos',  
                                    'detasignatura.secPlan',
                                    'detasignatura.instalaciones'
                        )
                        ->orderBy('detasignatura.secPlan', 'asc')
                        ->get();

            if (!$result) {
                $data = [
                    'message' => 'No existen datos',
                    'status' => 404
                ];
                return response()->json($data, 404);
            }
    
            $data = [
                'planes' => $result,
                'status' => 200
            ];
    
            return response()->json($data, 200);  
    }

    /**
     * Show the form for creating a new resource.
     * Show the form for creating a new resource.
     */
    public function store(Request $request){      
            
            $validator = Validator::make($request->all(), [
                                                    'idPlan' => 'required|max:1',
                                                    'idCarrera' => 'required|numeric',
                                                    'descripcion' => 'required|max:255',
                                                    'rvoe' => 'required|max:255',
                                                    'fechainicio' => 'required|date',
                                                    'idNivel' => 'required|numeric',
                                                    'idModalidad' => 'required|numeric',
                                                    'semestres' => 'required|numeric',
                                                    'vigente' => 'required|numeric',
                                                    'estatal' => 'required|numeric',
                                                    'decimales' => 'required|numeric',
                                                    'minAprobatoria' => 'required|numeric',
                                                    'grado' => 'required|max:255'
            ]);
           
            if ($validator->fails()) 
                return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            try{                                  
                $plan = Plan::create([
                                        'idPlan' => $request->idPlan,
                                        'idCarrera' => $request->idCarrera,
                                        'descripcion' => $request->descripcion,
                                        'rvoe' => $request->rvoe,
                                        'fechainicio' => $request->fechainicio,
                                        'idNivel' => $request->idNivel,
                                        'idModalidad' => $request->idModalidad,
                                        'semestres' =>$request->semestres,
                                        'vigente' => $request->vigente,
                                        'estatal' => $request->estatal,
                                        'decimales' => $request->decimales,
                                        'minAprobatoria' => $request->minAprobatoria,
                                        'grado' => $request->grado
                                    ]);
                } catch (QueryException $e) {                                
                    if ($e->getCode() == '23000') 
                        return $this->returnEstatus('El plan ya se encuentra dado de alta',400,null);
                    return $this->returnEstatus('Error al insertar la plan',400,null);
                    }      
                if (!$plan) 
                    return $this->returnEstatus('Error al crear el plan',500,null); 
                return $this->returnEstatus('El plan se creo',200,null); 
    }   

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

}
