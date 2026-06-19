<?php

namespace App\Http\Controllers\Api\admisiones; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\admisiones\Aspirante;
use App\Models\general\Persona;  
use App\Models\general\Integra;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;    
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Str; 
use App\Http\Controllers\Api\serviciosGenerales\ResultadoCargaExport;
   
class CargaAspController extends Controller{


public function store(Request $request)
{
    $renglon = 1;
    $resultados = [];

    foreach ($request->all() as $aspiranteData) {

        $renglon++;   
        DB::beginTransaction();
        try {
            $curp = strtoupper(trim($aspiranteData['curp'] ?? ''));

            if (!preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$/', $curp)) {
                $resultados[] = [
                    'renglon' => $renglon,
                    'estatus' => 'ERROR',
                    'mensaje' => 'CURP inválida',
                    'uid' => ''
                ];
                continue;
            }

            $grupo = trim($aspiranteData['grupo'] ?? '');

            if (!in_array(strlen($grupo), [4, 5])) {
                $resultados[] = [
                                'renglon' => $renglon,
                                'estatus' => 'ERROR',
                                'mensaje' => 'El grupo debe tener 4 o 5 caracteres',
                                'uid' => ''
                ];
                continue;
            }

            $idPlan = DB::table('plan')
                        ->where('idCarrera', $aspiranteData['idCarrera'])
                        ->where('idNivel', $aspiranteData['idNivel'])
                        ->where('vigente', 1)
                        ->value('idPlan');

                    if (!$idPlan) {
                        $resultados[] = [
                                'renglon' => $renglon,
                                'estatus' => 'ERROR',
                                'mensaje' => 'No existe un plan vigente para la carrera y nivel indicados',
                                'uid' => ''
                ];
                continue;
            }

            $existeCarrera = DB::table('carrera')
                                ->where('idCarrera', $aspiranteData['idCarrera'])
                                ->where('idNivel', $aspiranteData['idNivel'])
                                ->exists();

            if (!$existeCarrera) {
                $resultados[] = [
                                'renglon' => $renglon,
                                'estatus' => 'ERROR',
                                'mensaje' => 'La carrera no pertenece al nivel indicado',
                                'uid' => ''
                            ];
                continue;
            }

            $uid = Persona::where('curp', $curp)->value('uid');
            $uid = $uid ?: 0;

            if ($uid > 0) {
                $existeAlumno = DB::table('alumno')
                                ->where('uid', $uid)
                                ->where('idCarrera', $aspiranteData['idCarrera'])
                                ->where('idNivel', $aspiranteData['idNivel'])
                                ->exists();

                if ($existeAlumno) {
                    $resultados[] = [
                                    'renglon' => $renglon,
                                    'estatus' => 'ERROR',
                                    'mensaje' => 'La persona ya está dada de alta en la carrera',
                                    'uid' => $uid
                                ];
                    continue;
                }
            }

            $fechaCurp = substr($curp, 4, 6);
            $anio = substr($fechaCurp, 0, 2);
            $mes = substr($fechaCurp, 2, 2);
            $dia = substr($fechaCurp, 4, 2);

            $anioCompleto = ($anio >= date('y')) ? '19' . $anio : '20' . $anio;
            $fechaNacimiento = $anioCompleto . '-' . $mes . '-' . $dia;
            $sexo = substr($curp, 10, 1);
            $semestre = substr($grupo, strlen($grupo) == 5 ? 3 : 2, 1);
            $letraTurno = substr($grupo, strlen($grupo) == 5 ? 2 : 1, 1);

            $idTurno = DB::table('turno')
                            ->where('letra', $letraTurno)
                            ->value('idTurno');

            if (!$idTurno) {
                $resultados[] = [
                                'renglon' => $renglon,
                                'estatus' => 'ERROR',
                                'mensaje' => 'No existe el turno para el grupo indicado ',
                                'uid' => ''
                ];
                continue;
            }

            $newId = $uid;

            if ($uid == 0) {

                $maxId = Persona::max('uid');
                $newId = $maxId ? $maxId + 1 : 1;

                $persona = Persona::create([
                                    'uid' => $newId,
                                    'curp' => $curp,
                                    'nombre' => strtoupper(trim($aspiranteData['nombre'] ?? '')),
                                    'primerApellido' => strtoupper(trim(
                                        $aspiranteData['primerApellido']
                                        ?? $aspiranteData['paterno']
                                        ?? ''
                                    )),
                                    'segundoApellido' => strtoupper(trim(
                                        $aspiranteData['segundoApellido']
                                        ?? $aspiranteData['materno']
                                        ?? ''
                                    )),
                                    'fechaNacimiento' => $fechaNacimiento,
                                    'sexo' => strtoupper($sexo)
                                ]);

                if (!$persona) {
                    throw new \Exception('No fue posible crear la persona');
                }
            }

            $maxSeq = Integra::where('uid', $newId)
                              ->where('idRol', 3)
                              ->max('secuencia');

            $secuencialPers = $maxSeq ? $maxSeq + 1 : 1;

            Integra::create([
                            'uid' => $newId,
                            'secuencia' => $secuencialPers,
                            'idRol' => 3
            ]);

            Aspirante::create([
                                'uid' => $newId,
                                'secuencia' => $secuencialPers,
                                'idPeriodo' => $aspiranteData['idPeriodo'],
                                'idCarrera' => $aspiranteData['idCarrera'],
                                'idNivel' => $aspiranteData['idNivel'],
                                'idTurno' => $idTurno,
                                'uidEmpleado' => $aspiranteData['uidEmpleado'],
                                'fechaSolicitud' => now(),
                                'semestreIngreso' => $semestre,
                                'observaciones' => ''
            ]);  

            DB::commit();
            $result = DB::select('CALL conviertealumno(?, ?, ?, ?, ?, ?, ?,?)', 
                                                        [$newId,
                                                        $aspiranteData['idPeriodo'], 
                                                        $secuencialPers,
                                                        $aspiranteData['idCarrera'],
                                                        $idTurno,
                                                        $semestre,
                                                        $aspiranteData['uidEmpleado'],
                                                        $aspiranteData['idNivel']
                                                        ]);
      
           
            $resultados[] = [
                            'renglon' => $renglon,
                            'estatus' => 'OK',
                            'mensaje' => 'Alumno registrado correctamente ',
                            'uid' => $newId
            ];

        } catch (\Throwable $e) {
            $resultados[] = [
                            'renglon' => $renglon,
                            'estatus' => 'ERROR',
                            'mensaje' => $e->getMessage(),
                            'uid' => ''
                        ];
                         DB::rollBack();
        }
    }
    $nombreArchivo = 'resultado_carga_aspirantes_'.Str::random(8) . '.xlsx';
    $path = storage_path('app/public/' . $nombreArchivo);

    $export = new ResultadoCargaExport($resultados);
    Excel::store($export, $nombreArchivo, 'public');

        // Verifica si el archivo existe
        if (file_exists($path)) {
            return response()->json([
                'status' => 200,
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $nombreArchivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte'
            ]);
        }
    }
}