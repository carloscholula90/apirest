<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Grupo;
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GrupoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       $grupos = Grupo::all();
       return $this->returnData('grupos',$grupos,200);
    }
     /**
     * Display a listing of the resource.
     */
    public function show($idNivel,$idPeriodo,$idCarrera)
    {
       $carreraFormatted = str_pad($idCarrera, 2, '0', STR_PAD_LEFT);
       $grupos = DB::table('grupos')
                                ->distinct()  
                                ->select('grupo')
                                ->where('idNivel', $idNivel)
                                ->where('idPeriodo',$idPeriodo)
                                ->where('grupo', 'like', $carreraFormatted.'%')
                                ->get();
       return $this->returnData('grupos',$grupos,200);
    }

    public function cambioGrupo(Request $request){
      $periodo = DB::table('periodo')
                           ->select('idPeriodo','idTurno',
                           'SUBSTRING(cl.grupo, CASE WHEN LENGTH('.$request->newGrupo.') = 4 THEN 3 WHEN LENGTH('.$request->newGrupo.') = 5 THEN 4 ELSE 4 END, 1)'
                           )
                           ->join('turno as t', function($join) {
                                $join->on('t.letra', '=', DB::raw(
                                'SUBSTRING(cl.grupo, CASE WHEN LENGTH('.$request->newGrupo.') = 4 THEN 2 WHEN LENGTH('.$request->newGrupo.') = 5 THEN 3 ELSE 3 END, 1)'
                                 ));})
                           ->where('activo', 1)
                           ->where('idNivel', $request->idNivel)
                           ->first();
      //Validar el turno de para ver si cambia de costos
      DB::statement("SET @origen = 'LARAVEL'");
      DB::statement("SET @uidMvto = ?", [$request->uidMvto]);
                    
      foreach ($request->grupos as $grupos){

         $ciclo = DB::table('ciclos')
               ->where('idPeriodo', $request->idPeriodo)
               ->where('uid', $grupos->uid)
               ->where('secuencia', $grupos->secuencia)
               ->first();

         $indexciclo = $ciclo->indexciclo ?? null;

         DB::table('ciclos')
                        ->where('idPeriodo', $request->idPeriodo)
                        ->where('uid',$grupos->uid)
                        ->where('secuencia', $grupos->secuencia)
                        ->update(['grupo' => $request->newGrupo,
                                 'semestre'=> $request->newSemestre]);

                 //valido si hay movimientos en el estado de cuenta
                 
            $colegiaturasPagadas = DB::table('edocta as edo')
                                       ->where('edo.uid', $grupos->uid)
                                       ->where('edo.secuencia', $grupos->secuencia)
                                       ->where('edo.idPeriodo', $servicios->idPeriodo)
                                       ->where('edo.tipomovto', 'A')
                                       ->whereIn('edo.idServicio', [
                                                $servicios->idServicioInscripcion,
                                                $servicios->idServicioColegiatura,
                                                $servicios->idServicioRecargo
                            ])
                            ->orderBy('edo.consecutivo', 'asc')
                            ->exists();

               //Cambio de grupo borramos materias
               if (!$colegiaturasPagadas) {
                     DB::table('edocta as edo')
                        ->where('edo.uid', $grupos->uid)
                        ->where('edo.secuencia', $grupos->secuencia)
                        ->where('edo.idPeriodo', $servicios->idPeriodo)
                        ->where('edo.tipomovto', 'C')
                        ->whereIn('edo.idServicio', [
                           $servicios->idServicioInscripcion,
                           $servicios->idServicioColegiatura,
                           $servicios->idServicioRecargo
                        ])
                        ->delete();

                     //Actualizamos cargos
                     $result = DB::select('CALL GeneraCargosInscrip(?, ?, ?, ?, ?, ?, ?)', 
                                                        [$request->idNivel,$request->idPeriodo, 
                                                         $request->idCarrera,$request->newSemestre,
                                                         $grupos->uid,$grupos->secuencia,$grupos->idTurno
                                                        ]);

               }
               if($indexciclo!=null){
                     DB::table('calificaciones')
                           ->where('indexCiclo', $indexciclo)
                           ->delete();

                     $result = DB::select('CALL GeneraCargaAcad(?, ?, ?, ?, ?, ?, ?, ?)', 
                                                        [$request->idNivel,$request->idPeriodo, 
                                                         $grupos->uid,$grupos->matricula,
                                                         $grupos->semestre,$request->idCarrera,
                                                         $grupos->plan,$request->grupo
                                                        ]);

               
               }

      }
      return $this->returnData('Registros actualizados',null,200);
    }
}
