<?php

namespace App\Http\Controllers\Api\escolar;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
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
                'alumnos' => $result,
                'status' => 200
            ];
    
            return response()->json($data, 200);  
    }

    /**
     * Show the form for creating a new resource.
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
