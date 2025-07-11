<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;  


class InscripcionesController extends Controller
{
    public function index($idNivel){  

       $idPeriodo = DB::table('periodo')
                    ->where('idNivel', $idNivel)
                    ->where('inscripciones', 1)
                    ->value('idPeriodo');

       $result = DB::table('ciclos as c')
                ->join('alumno as a', function ($join) {
                    $join->on('a.uid', '=', 'c.uid')
                        ->on('a.secuencia', '=', 'c.secuencia');
                })
                ->join('carrera as car', function ($join) {
                    $join->on('car.idNivel', '=', 'c.idNivel')
                        ->on('car.idCarrera', '=', 'a.idCarrera');
                })
                ->join('persona as p', 'p.uid', '=', 'a.uid')
                ->leftJoin('bloqueoPersonas as ba', function ($join) {
                    $join->on('ba.uid', '=', 'a.uid')
                        ->on('ba.secuencia', '=', 'a.secuencia')
                        ->where('ba.idBloqueo', '=', 1)
                        ->where('ba.BloqueoActivo', '=', 1);
                })
                ->leftJoin('bloqueos as b1', 'b1.idBloqueo', '=', 'ba.idBloqueo')
                ->leftJoin('bloqueoPersonas as bac', function ($join) {
                    $join->on('bac.uid', '=', 'a.uid')
                        ->on('bac.secuencia', '=', 'a.secuencia')
                        ->where('bac.idBloqueo', '=', 2)
                        ->where('bac.BloqueoActivo', '=', 1);
                })
                ->leftJoin('bloqueos as b2', 'b2.idBloqueo', '=', 'bac.idBloqueo')
                ->leftJoin('bloqueoPersonas as bc', function ($join) {
                    $join->on('bc.uid', '=', 'a.uid')
                        ->on('bc.secuencia', '=', 'a.secuencia')
                        ->where('bc.idBloqueo', '=', 3)
                        ->where('bc.BloqueoActivo', '=', 1);
                })
                ->leftJoin('bloqueos as b3', 'b3.idBloqueo', '=', 'bc.idBloqueo')
                ->leftJoin('bloqueoPersonas as bd', function ($join) {
                    $join->on('bd.uid', '=', 'a.uid')
                        ->on('bd.secuencia', '=', 'a.secuencia')
                        ->where('bd.idBloqueo', '=', 4)
                        ->where('bd.BloqueoActivo', '=', 1);
                })
                ->leftJoin('bloqueos as b4', 'b4.idBloqueo', '=', 'bd.idBloqueo')
                ->leftJoin('grupos as gp', function($join) use ($idPeriodo) {
                            $join->on(DB::raw("gp.grupo"), '=', 
                                DB::raw("CONCAT(SUBSTRING(c.grupo, 1, 3),
                                CAST(SUBSTRING(c.grupo, 4, 1) AS UNSIGNED) + 1,
                                SUBSTRING(c.grupo, 5, 1)
                            )"))
                            ->where('gp.idPeriodo', '=', $idPeriodo);
                        })
                ->where('c.idNivel', $idNivel)
                ->where('c.idPeriodo', $idPeriodo-1)   
                ->whereNotExists(function ($query) use ($idPeriodo) {
                    $query->select(DB::raw(1))
                        ->from('ciclos as c2')
                        ->whereRaw('c2.uid = c.uid')
                        ->whereRaw('c2.secuencia = c.secuencia')
                        ->where('c2.idPeriodo', $idPeriodo);
                })
                ->select([
                            'a.uid',
                            'a.idPlan',
                            'a.matricula',
                            'p.nombre',
                            'p.primerApellido',
                            'p.segundoApellido',
                            'car.descripcion',
                            'c.idNivel',
                            DB::raw( $idPeriodo),
                            'a.idCarrera',
                            'gp.grupo',
                            DB::raw("IF(b1.descripcion = 'ADEUDO', TRUE, FALSE) as adeudo"),
                            DB::raw("IF(b2.descripcion = 'ACADEMICO', TRUE, FALSE) as academico"),
                            DB::raw("IF(b3.descripcion = 'CASTIGO', TRUE, FALSE) as castigo"),
                            DB::raw("IF(b4.descripcion = 'DOCUMENTOS', TRUE, FALSE) as documentos")
                ])
                ->distinct()
                ->get();
        Log::info('$idPeriodo:'.$idPeriodo); 
        return response()->json($result, 200);
    }

     public function store(Request $request){

        $validator = Validator::make($request->all(), [
                            'idNivel' => 'required|max:255',
                            'idCarrera' => 'required|max:255',
                            'idPeriodo' => 'required|max:255',
                            'matricula'=> 'required|array',
                            'uid'=> 'required|array',
                            'plan'=> 'required|array',
                            'grupo'=> 'required|array'

        ]);

        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        for ($indx = 0; $indx <$cantidad; $indx++){
                //Log::info('indx :'.$indx);
                //Log::info('grupo :'.$grupoB[$indx]);
                //Log::info('capacidad :'.$capacidadB[$indx]);
                //Log::info('semestre :'.$semestreB[$indx]);   
                $result = DB::select('CALL creaoferta(?, ?, ?, ?, ?, ?, ?, ?, ?)', 
                                                        [$request->nivel,$request->periodo, 
                                                         $request->escuela, $request->pl, 
                                                         $request->turno, $request->modalidad,$grupoB[$indx],
                                                         $semestreB[$indx],$capacidadB[$indx]]);
                Log::info('resultado :',$result);   

        }
        $data = ['msj' => 'Proceso exitoso','status' => 200];
    
        return response()->json($data, 200);

    }
}
