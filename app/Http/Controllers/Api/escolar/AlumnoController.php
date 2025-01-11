<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;  
use Illuminate\Support\Facades\DB;
use App\Models\escolar\Alumno;
use Illuminate\Http\Request;

class AlumnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
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
    public function show(Alumno $alumno)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Alumno $alumno)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Alumno $alumno)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Alumno $alumno)
    {
        //
    }

    public function getAlumno($uid){
        $alumnos = DB::table('alumno')->join('integra', function($join) {
                    $join->on('integra.uid', '=', 'alumno.uid')
                    ->on('integra.secuencia', '=', 'alumno.secuencia');})
                    ->join('nivel', 'nivel.idNivel', '=', 'alumno.idNivel')
                    ->join('carrera', 'carrera.idCarrera', '=', 'alumno.idCarrera')
                    ->join('persona', 'persona.uid', '=', 'alumno.uid')
                    ->leftJoin('estado', function($join) {
                    $join->on('estado.idEstado', '=', 'persona.idEstado')->on('estado.idPais', '=', 'persona.idPais');
            })
            ->leftJoin('pais', 'pais.idPais', '=', 'persona.idPais')
            ->leftJoin('edoCivil', 'edoCivil.idEdoCivil', '=', 'persona.idEdoCivil')
            ->where('alumno.uid', '=', $uid)
            ->select(   'alumno.uid',
                        'alumno.idNivel',
                        'nivel.descripcion as nivel',
                        'carrera.descripcion as nombreCarrera',
                        'persona.curp',
                        'persona.nombre',
                        'persona.primerapellido',
                        'persona.segundoapellido',
                        'persona.sexo',
                        'persona.rfc',
                        DB::raw("CASE WHEN integra.activo = 1 THEN 'ACTIVO' ELSE 'INACTIVO' END as activo"),
                        'estado.descripcion as estado',
                        'pais.descripcion as pais',
                        'edoCivil.descripcion as edocivil'
            )
            ->orderBy('nivel.idNivel', 'desc')
            ->get();

            if (!$alumnos) {
                $data = [
                    'message' => 'Alumno no encontrado',
                    'status' => 404
                ];
                return response()->json($data, 404);
            }
    
            $data = [
                'alumnos' => $alumnos,
                'status' => 200
            ];
    
            return response()->json($data, 200);
    }

}
