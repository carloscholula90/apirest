<?php

namespace App\Http\Controllers\Api\tesoreria;  
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\tesoreria\Servicio;
use App\Models\tesoreria\ServicioCarrera;
use Illuminate\Support\Facades\Validator;

class ServicioCarreraController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index()
{
        $rows = DB::select(" SELECT 
                                    n.descripcion AS licenciatura,
                                    sc.idNivel,
                                    sc.idCarrera,
                                    car.descripcion AS carrera,
                                    p.idPeriodo,
                                    p.descripcion AS periodo,
                                    s.idServicio,
                                    s.descripcion AS servicio,
                                    s.tarjeta,
                                    s.efectivo,
                                    s.cargoAutomatico,
                                    sc.semestre,
                                    sc.aplicaIns,
                                    sc.idTurno
                                FROM servicio s
                                INNER JOIN servicioCarrera sc ON sc.idServicio = s.idServicio
                                INNER JOIN nivel n ON n.idNivel = sc.idNivel
                                INNER JOIN carrera car ON car.idCarrera = sc.idCarrera AND car.idNivel = sc.idNivel
                                INNER JOIN periodo p ON p.idNivel = sc.idNivel AND p.idPeriodo = sc.idPeriodo
                                INNER JOIN periodo activo ON activo.idNivel = sc.idNivel AND activo.activo = 1
                                WHERE p.idPeriodo IN (activo.idPeriodo, activo.idPeriodo + 1)
                                ORDER BY n.descripcion, sc.idCarrera, p.idPeriodo, s.descripcion
                            ");


       $estructura = [];

            foreach ($rows as $row) {
                if (!isset($estructura[$row->licenciatura])) {
                    $estructura[$row->licenciatura] = [
                        'nivel' => $row->licenciatura,
                        'idNivel' => $row->idNivel,
                        'carreras' => []
                    ];
                }

                $carreras =& $estructura[$row->licenciatura]['carreras'];
                if (!isset($carreras[$row->idCarrera])) {
                    $carreras[$row->idCarrera] = [
                        'idCarrera' => $row->idCarrera,
                        'carrera' => $row->carrera,                        
                        'periodos' => []
                    ];
                }

                $periodos =& $carreras[$row->idCarrera]['periodos'];
                if (!isset($periodos[$row->idPeriodo])) {
                    $periodos[$row->idPeriodo] = [
                        'idPeriodo' => $row->idPeriodo,
                        'descripcion' => $row->periodo,
                        'servicios' => []
                    ];
                }

                $periodos[$row->idPeriodo]['servicios'][] = [
                    'idServicio' => $row->idServicio,
                    'descripcion' => $row->servicio,
                    'cargoAutomatico' => $row->cargoAutomatico,
                    'efectivo' => $row->efectivo,
                    'tarjeta' => $row->tarjeta,
                    'idTurno' => $row->idTurno,
                    'semestre'=>  $row->semestre,
                    'aplicaIns'=>  $row->aplicaIns
                    
                ];
            }

            // Convertir a arrays indexados
            $final = array_values(array_map(function($licenciatura) {
                $licenciatura['carreras'] = array_values(array_map(function($carrera) {
                    $carrera['periodos'] = array_values($carrera['periodos']);
                    return $carrera;
                }, $licenciatura['carreras']));
                return $licenciatura;
            }, $estructura));

   return response()->json($final);
}

 public function store(Request $request) {
        
        $validator = Validator::make($request->all(), [
                                    'descripcion' => 'required|max:255',
                                    'efectivo' => 'required|numeric',
                                    'tarjeta' => 'required|numeric',
                                    'cargoAutomatico' => 'required|numeric',
                                    'idNivel' => 'required|numeric',
                                    'idPeriodo' => 'required|numeric',
                                    'semestre' => 'required|numeric',
                                    'monto' => 'required|numeric',
                                    'aplicaIns' => 'required|numeric',
                                    'carreras' => 'required|array',
                                    'turnos' => 'required|array'
        ]);
        $idServicio =0;
        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
        if(isset($request->idServicio)) {
            //El servicio ya existe entonces solo actualiza
            $idServicio = $request->idServicio;
            $servicio = Servicio::find($request->idServicio);     
            $servicio->efectivo = $request->efectivo;
            $servicio->tarjeta = $request->tarjeta;
            $servicio->cargoAutomatico = $request->cargoAutomatico;       
            $carreras->save(); 
        }
        else{
            $maxId = Servicio::max('idServicio');
            $idServicio = $maxId ? $maxId + 1 : 1;
            $servicio = Servicio::create([
                                'idServicio' => $idServicio,
                                'descripcion' => strtoupper(trim($request->descripcion)),
                                'efectivo' => $request->efectivo,
                                'tarjeta' => $request->tarjeta,
                                'cargoAutomatico' => $request->cargoAutomatico
                    ]);
            }

            $cantidad = count($request->carreras);
            $tamanio= count($request->turnos);
            $carreras = $request->carreras;
            $turnos = $request->turnos;
        for ($indx = 0; $indx <$cantidad; $indx++){
            $carreraB =$carreras[$indx];
            
            for($indx2=0; $indx2< $tamanio;$indx2++){
             $servicioC =    ServicioCarrera::create([
                                'idNivel'=> $request->idNivel,
                                'idPeriodo'=> $request->idPeriodo,
                                'idCarrera' =>  $carreraB,
                                'idServicio' => $idServicio,
                                'idTurno' => $turnos[$indx2],
                                'semestre'=> $request->semestre,
                                'monto'=> $request->monto,
                                'aplicaIns'=> $request->aplicaIns
                    ]);
                    }
        }
        return $this->returnData('servicios',null,200);
    }

    public function destroy($idNivel,$idPeriodo,$idCarrera,$idServicio,$idTurno)
    {
        $destroy = DB::table('servicioCarrera')
                            ->where('idNivel', $idNivel  )
                            ->where('idPeriodo', $idPeriodo)
                            ->where('idCarrera', $idCarrera)
                            ->where('idServicio', $idServicio)
                            ->where('idTurno', $idTurno)
                            ->delete();

        if ($destroy === 0) 
            return $this->returnEstatus('Error en la eliminacion', 404, null);
                
        return $this->returnEstatus('Registro eliminado',200,null); 
    }
    
}