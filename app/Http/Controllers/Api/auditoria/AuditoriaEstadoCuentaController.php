<?php

namespace App\Http\Controllers\Api\auditoria;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuditoriaEstadoCuentaController extends Controller
{
    public function estadoCuenta($matricula, $idPeriodo)
    {
        $validator = Validator::make([
            'matricula' => $matricula,
            'idPeriodo' => $idPeriodo,
        ], [
            'matricula' => 'required|integer',
            'idPeriodo' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $auditoria = DB::select($this->queryAuditoriaEstadoCuenta(), [
            $matricula,
            $idPeriodo,
        ]);

        if (empty($auditoria)) {
            return $this->returnEstatus('No se encontraron registros de auditoria', 404, null);
        }

        return $this->returnData('auditoria', $auditoria, 200);
    }

    private function queryAuditoriaEstadoCuenta()
    {
        return <<<'SQL'
WITH logs_base AS (
    SELECT
        JSON_UNQUOTE(JSON_EXTRACT(log, '$.uid')) AS uid,
        JSON_UNQUOTE(JSON_EXTRACT(log, '$.idServicio')) AS idServicio,
        JSON_UNQUOTE(JSON_EXTRACT(log, '$.operacion')) AS operacion,
        JSON_UNQUOTE(JSON_EXTRACT(log, '$.origen')) AS origen,
        JSON_UNQUOTE(JSON_EXTRACT(log, '$.uidMvto')) AS uidMvto,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(log, '$.fechaMovimientoReal')) AS DATETIME(6)) AS fechaAccion,
        log
    FROM siaweb_logs.edoCta
    INNER JOIN siaweb_produccion.alumno
        ON alumno.matricula = ?
    WHERE JSON_UNQUOTE(JSON_EXTRACT(siaweb_logs.edoCta.log, '$.uid')) = alumno.uid
      AND alumno.secuencia = JSON_UNQUOTE(JSON_EXTRACT(siaweb_logs.edoCta.log, '$.secuencia'))
      AND JSON_UNQUOTE(JSON_EXTRACT(siaweb_logs.edoCta.log, '$.idPeriodo')) = ?
)
SELECT
    logs_base.fechaAccion,
    CASE
        WHEN logs_base.operacion = 'I' THEN
            CONCAT(
                'Se agrego un ',
                CASE JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.tipomovto'))
                    WHEN 'C' THEN 'cargo'
                    WHEN 'A' THEN 'abono'
                    ELSE 'movimiento'
                END,
                ' de ',
                servicio.descripcion,
                ' por $',
                FORMAT(CAST(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.importe')) AS DECIMAL(12,2)), 2)
            )
        WHEN logs_base.operacion = 'D' THEN
            CONCAT(
                'Se elimino un ',
                CASE JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.tipomovto'))
                    WHEN 'C' THEN 'cargo'
                    WHEN 'A' THEN 'abono'
                    ELSE 'movimiento'
                END,
                ' de ',
                servicio.descripcion,
                ' por $',
                FORMAT(CAST(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.importe')) AS DECIMAL(12,2)), 2)
            )
        WHEN logs_base.operacion = 'U' THEN
            CASE
                WHEN GREATEST(JSON_LENGTH(logs_base.log) - 8, 0) = 0 THEN
                    CONCAT('Se registro una actualizacion en ', servicio.descripcion, ', pero no cambio ningun campo auditado')
                ELSE
                    CONCAT(
                        'Se realizo modificacion en ',
                        CASE
                            WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.referenciaI') = 1 THEN
                                CONCAT(
                                    'referencia ',
                                    COALESCE(
                                        ELT(
                                            CAST(RIGHT(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.referenciaI')), 2) AS UNSIGNED),
                                            'enero',
                                            'febrero',
                                            'marzo',
                                            'abril',
                                            'mayo',
                                            'junio',
                                            'julio',
                                            'agosto',
                                            'septiembre',
                                            'octubre',
                                            'noviembre',
                                            'diciembre'
                                        ),
                                        'mes no valido'
                                    )
                                )
                        END,
                        ' ',
                        servicio.descripcion,
                        ': ',
                        CONCAT_WS(
                            ', ',
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.importe') = 1 THEN
                                    CONCAT('importe $', FORMAT(CAST(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.importe')) AS DECIMAL(12,2)), 2))
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.idPeriodo') = 1 THEN
                                    CONCAT('periodo a ', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.idPeriodo')), 'sin valor'))
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.fechaMovto') = 1 THEN
                                    CONCAT('fecha del movimiento a ', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.fechaMovto')), 'sin valor'))
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.idformaPago') = 1 THEN
                                    CONCAT('forma de pago a ', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.idformaPago')), 'sin valor'))
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.cuatrodigitos') = 1 THEN
                                    CONCAT('ultimos cuatro digitos a ', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.cuatrodigitos')), 'sin valor'))
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.tipomovto') = 1 THEN
                                    CONCAT(
                                        'tipo de movimiento a ',
                                        CASE JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.tipomovto'))
                                            WHEN 'C' THEN 'cargo'
                                            WHEN 'A' THEN 'abono'
                                            ELSE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.tipomovto')), 'sin valor')
                                        END
                                    )
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.FechaPago') = 1 THEN
                                    CONCAT('fecha de pago a ', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.FechaPago')), 'sin valor'))
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.fechaVencimiento') = 1 THEN
                                    CONCAT('fecha de vencimiento a ', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.fechaVencimiento')), 'sin valor'))
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.doctoImpreso') = 1 THEN
                                    CONCAT('documento impreso a ', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.doctoImpreso')), 'sin valor'))
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.comprobante') = 1 THEN
                                    CONCAT('comprobante a ', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.comprobante')), 'sin valor'))
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.folio') = 1 THEN
                                    CONCAT('folio a ', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.folio')), 'sin valor'))
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.parcialidad') = 1 THEN
                                    CONCAT('parcialidad a ', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.parcialidad')), 'sin valor'))
                            END,
                            CASE
                                WHEN JSON_CONTAINS_PATH(logs_base.log, 'one', '$.transaccion') = 1 THEN
                                    CONCAT('transaccion a ', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(logs_base.log, '$.transaccion')), 'sin valor'))
                            END
                        )
                    )
            END
        ELSE
            'No fue posible identificar la accion'
    END AS descripcion,
    CASE
        WHEN logs_base.origen <> 'LARAVEL' THEN 'ADMINISTRADOR'
        ELSE COALESCE(
            NULLIF(CONCAT_WS(' ', persona.nombre, persona.primerApellido, persona.segundoApellido), ''),
            CONCAT('USUARIO ', logs_base.uidMvto)
        )
    END AS realizadoPor,
    logs_base.operacion
FROM logs_base
INNER JOIN siaweb_produccion.servicio AS servicio
    ON servicio.idServicio = logs_base.idServicio
LEFT JOIN siaweb_produccion.persona AS persona
    ON persona.uid = logs_base.uidMvto
WHERE logs_base.fechaAccion IS NOT NULL
ORDER BY logs_base.fechaAccion DESC
SQL;
    }
}
