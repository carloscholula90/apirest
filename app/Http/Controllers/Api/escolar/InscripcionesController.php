<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;
use Illuminate\Support\Facades\DB;

class InscripcionesController extends Controller
{
    public function index(){

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
                ->where('c.idNivel', 5)
                ->where('c.idPeriodo', 109) // (110 - 1)
                ->select([
                            'a.uid as uid',
                            'a.matricula as matricula',
                            'p.nombre',
                            'p.primerApellido as Paterno',
                            'p.segundoApellido as Materno',
                            'car.descripcion as carrera',
                            DB::raw("IF(b1.descripcion = 'ADEUDO', TRUE, FALSE) as adeudo"),
                            DB::raw("IF(b2.descripcion = 'ACADEMICO', TRUE, FALSE) as academico"),
                            DB::raw("IF(b3.descripcion = 'CASTIGO', TRUE, FALSE) as castigo"),
                            DB::raw("IF(b4.descripcion = 'DOCUMENTOS', TRUE, FALSE) as documentos")
                ])
                ->get();

        return response()->json($result, 200);
    }

   
}
