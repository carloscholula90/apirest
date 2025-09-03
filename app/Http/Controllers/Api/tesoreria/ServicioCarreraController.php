<?php

namespace App\Http\Controllers\Api\tesoreria;  
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


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
                                    s.tarjeta AS tarjeta,
                                    s.efectivo AS efectivo,
                                    s.cargoAutomatico AS cargoAutomatico
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
                    'tarjeta' => $row->tarjeta
                    
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
    
}