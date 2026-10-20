<?php

namespace App\Http\Controllers\Api\auditoria;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuditoriaBajasController extends Controller
{
    public function bajas($uid)
    {
        $validator = Validator::make([
            'uid' => $uid,
        ], [
            'uid' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validación de los datos', 400, $validator->errors());
        }

        $auditoria = DB::select($this->queryAuditoriaBajas(), [
            $uid,
        ]);

        if (empty($auditoria)) {
            return $this->returnEstatus('No se encontraron registros de auditoría', 404, null);
        }

        return $this->returnData('auditoria', $auditoria, 200);
    }

    /*
     * siaweb_logs.bajas lo llena el trigger trg_alumno (y trg_alumno_alta) con:
     * uid, secuencia, operacion, origen, uidMvto, movimiento y fechaBaja
     * (o fechaAlta cuando el alumno se da de alta como no activo).
     */
    private function queryAuditoriaBajas()
    {
        return <<<'SQL'
WITH logs_base AS (
    SELECT
        JSON_UNQUOTE(JSON_EXTRACT(log, '$.uid')) AS uid,
        JSON_UNQUOTE(JSON_EXTRACT(log, '$.secuencia')) AS secuencia,
        JSON_UNQUOTE(JSON_EXTRACT(log, '$.operacion')) AS operacion,
        JSON_UNQUOTE(JSON_EXTRACT(log, '$.origen')) AS origen,
        JSON_UNQUOTE(JSON_EXTRACT(log, '$.uidMvto')) AS uidMvto,
        JSON_UNQUOTE(JSON_EXTRACT(log, '$.movimiento')) AS movimiento,
        CAST(
            COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(log, '$.fechaBaja')),
                JSON_UNQUOTE(JSON_EXTRACT(log, '$.fechaAlta'))
            ) AS DATETIME(6)
        ) AS fechaAccion
    FROM siaweb_logs.bajas
    WHERE JSON_UNQUOTE(JSON_EXTRACT(siaweb_logs.bajas.log, '$.uid')) = ?
)
SELECT
    logs_base.fechaAccion,
    COALESCE(logs_base.movimiento, 'No fue posible identificar la acción') AS descripcion,
    CASE
        WHEN logs_base.origen <> 'LARAVEL' THEN 'ADMINISTRADOR'
        ELSE COALESCE(
            NULLIF(CONCAT_WS(' ', persona.nombre, persona.primerApellido, persona.segundoApellido), ''),
            CONCAT('USUARIO ', logs_base.uidMvto)
        )
    END AS realizadoPor,
    logs_base.operacion,
    logs_base.uid,
    logs_base.secuencia
FROM logs_base
LEFT JOIN siaweb_produccion.persona AS persona
    ON persona.uid = logs_base.uidMvto
WHERE logs_base.fechaAccion IS NOT NULL
ORDER BY logs_base.fechaAccion DESC
SQL;
    }
}
