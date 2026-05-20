<?php

namespace App\Http\Controllers\Api\general;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;    
use Illuminate\Support\Facades\DB;

class KpisController extends Controller{    

 public function getKpis()
{
    $periodo = DB::table('periodo')
        ->where('activo', 1)
        ->where('idNivel', 5)
        ->value('idPeriodo');

    if (!$periodo) {
        return $this->returnEstatus('No existe periodo activo', 404, null);
    }

    $periodoAnterior = $periodo - 1;

    $datos = DB::table(DB::raw('(SELECT 1) x'))
        ->selectRaw("
            (
                SELECT COUNT(*)
                FROM alumno al
                INNER JOIN ciclos cl
                    ON cl.uid = al.uid
                    AND cl.secuencia = al.secuencia
                WHERE cl.idPeriodo = ?
            ) AS inscritos,

            (
                SELECT SUM(CASE WHEN asp.uid IS NULL THEN 0 ELSE 1 END)
                FROM alumno al
                INNER JOIN ciclos cl
                    ON cl.uid = al.uid
                    AND cl.secuencia = al.secuencia
                LEFT JOIN aspirante asp
                    ON asp.uid = al.uid
                    AND asp.idNivel = al.idNivel
                    AND asp.idPeriodo = cl.idPeriodo
                    AND asp.idCarrera = al.idCarrera
                WHERE cl.idPeriodo = ?
            ) AS aspirantes,

            (
                SELECT
                    COUNT(CASE WHEN cl.idPeriodo = ?  THEN 1 END) /
                    NULLIF(COUNT(CASE WHEN cl.idPeriodo = ?  THEN 1 END),0)
                FROM alumno al
                INNER JOIN ciclos cl 
                    ON cl.uid = al.uid
                    AND cl.secuencia = al.secuencia
                WHERE cl.idPeriodo IN (?,?) 
            ) AS eficiencia,

            (
                SELECT SUM(edo.importe)
                FROM edocta edo
                INNER JOIN alumno al
                    ON al.uid = edo.uid
                    AND al.secuencia = edo.secuencia
                WHERE edo.tipomovto='A'
                AND edo.idPeriodo = ? 
            ) AS importe,
            (
                SELECT
                    (
                        SUM(
                            CASE
                                WHEN edo.FechaPago < CURDATE()
                                AND ab.uid IS NULL
                                THEN edo.importe
                                ELSE 0
                            END
                        ) * 100
                    ) / NULLIF(SUM(edo.importe),0)
                FROM edocta edo
                INNER JOIN alumno al
                    ON al.uid = edo.uid
                    AND al.secuencia = edo.secuencia
                INNER JOIN configuracionTesoreria ct
                    ON ct.idNivel = al.idNivel
                LEFT JOIN edocta ab
                    ON ab.uid = edo.uid
                    AND ab.parcialidad = edo.parcialidad
                    AND ab.tipomovto = 'A'
                    AND edo.idServicio = ab.idServicio
                WHERE edo.idPeriodo = ?
                AND edo.tipomovto = 'C'
                AND edo.idServicio = ct.idServicioColegiatura
            ) AS morosidad,

            (
                SELECT AVG(calif.cf)
                FROM alumno al
                INNER JOIN plan
                    ON plan.idPlan = al.idPlan
                    AND plan.idNivel = al.idNivel
                    AND al.idCarrera = plan.idCarrera
                INNER JOIN ciclos cl
                    ON cl.uid = al.uid
                    AND cl.secuencia = al.secuencia
                INNER JOIN calificaciones calif
                    ON calif.indexCiclo = cl.indexCiclo
                WHERE cl.idPeriodo = ?
            ) AS promedio,

            (
                SELECT
                    (
                        SUM(
                            CASE
                                WHEN plan.minAprobatoria >= calif.cf THEN 0
                                ELSE 1
                            END
                        ) * 100
                    ) / NULLIF(COUNT(*),0)
                FROM alumno al
                INNER JOIN plan
                    ON plan.idPlan = al.idPlan
                    AND plan.idNivel = al.idNivel
                    AND al.idCarrera = plan.idCarrera
                INNER JOIN ciclos cl
                    ON cl.uid = al.uid
                    AND cl.secuencia = al.secuencia
                INNER JOIN calificaciones calif
                    ON calif.indexCiclo = cl.indexCiclo
                WHERE cl.idPeriodo = ? 
            ) AS aprobada,
             0 AS ocupacion
        ", [
            $periodo,
            $periodo,
            $periodoAnterior,
            $periodo,
            $periodoAnterior,
            $periodo,
            $periodo,
            $periodo,
            $periodo,
            $periodo,
        ])
        ->first();

    if (!$datos) {
        return $this->returnEstatus('No se encontraron datos', 200, null);
    }

    return $this->returnData('kpis', $datos, 200);
}

   
  
}
