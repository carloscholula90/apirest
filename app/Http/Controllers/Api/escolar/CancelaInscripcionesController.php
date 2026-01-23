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


class CancelaInscripcionesController extends Controller
{
    public function index($idNivel){  

       $idPeriodo = DB::table('periodo')
                    ->where('idNivel', $idNivel)
                    ->where('inscripciones', 1)
                    ->value('idPeriodo');
        
       $descripcionP = DB::table('periodo')
                    ->where('idNivel', $idNivel)
                    ->where('inscripciones', 1)
                    ->value('descripcion');

       $result = DB::table('ciclos as c')
                ->join('alumno as a', function ($join) {
                    $join->on('a.uid', '=', 'c.uid')
                        ->on('a.secuencia', '=', 'c.secuencia');
                })
                ->join('carrera as car', function ($join) {
                    $join->on('car.idNivel', '=', 'c.idNivel')
                        ->on('car.idCarrera', '=', 'a.idCarrera');
                })
                ->join('nivel as nv', 'nv.idNivel', '=', 'c.idNivel')
                ->join('persona as p', 'p.uid', '=', 'a.uid')
                ->where('c.idNivel', $idNivel)
                ->where('c.idPeriodo', $idPeriodo)  
                ->select([   
                            'a.uid',
                            'a.matricula',
                            'p.nombre',
                            'p.primerApellido',
                            'p.segundoApellido', 
                            'c.idNivel',
                            'nv.descripcion as nivel',
                            'a.idCarrera',                            
                            'car.descripcion AS carrera',
                            DB::raw("'" . $idPeriodo . "' as idPeriodo"),
                            DB::raw("'" . $descripcionP . "' as periodo")
                ])
                ->distinct()
                ->get();
         return response()->json($result, 200);
    }

     public function store(Request $request){

        $validator = Validator::make($request->all(), [                           
                            'idPeriodo' => 'required|max:255',
                            'uids'=> 'required|array',
                            'matriculas'=> 'required|array'

        ]);

        $uids = $request->uids;
        $matriculas = $request->matriculas;
       
        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        $cantidad = count($uids);

        for ($indx = 0; $indx <$cantidad; $indx++){
                //Log::info('indx :'.$indx);
                //Log::info('uids :'.$uids[$indx]);
                //Log::info('matriculas :'.$matriculas[$indx]);
                    
                $result = DB::select('CALL cancelaInscripcion(?, ?, ?)', 
                                                        [$uids[$indx],$matriculas[$indx],
                                                         $request->idPeriodo]);
                //Log::info('resultado :',$result);   

        }
        $data = ['msj' => 'Proceso exitoso','status' => 200];
    
        return response()->json($data, 200);

    }
}
