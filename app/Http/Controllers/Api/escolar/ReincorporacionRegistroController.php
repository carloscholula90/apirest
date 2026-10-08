<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PDO;
use PDOException;
use Throwable;

class ReincorporacionRegistroController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required|integer|min:1|max:2147483647',
            'idNivel' => 'required|integer|min:1|max:2147483647',
            'idCarrera' => 'required|integer|min:1|max:2147483647',
            'semestre' => 'required|integer|min:1|max:2147483647',
            'grupo' => 'required|string|max:8',
            'idPeriodo' => 'required|integer|min:1|max:2147483647',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Los datos de reincorporacion no son validos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $input = $validator->validated();
        $statement = null;
        try {
            // El procedimiento administra COMMIT/ROLLBACK; no envolver en DB::transaction.
            $statement = DB::connection()->getPdo()->prepare('CALL reincorporacion(?, ?, ?, ?, ?, ?)');
            $statement->execute([
                (int) $input['uid'], (int) $input['idNivel'], (int) $input['idCarrera'],
                (int) $input['semestre'], $input['grupo'], (int) $input['idPeriodo'],
            ]);

            $sets = [];
            $message = null;
            do {
                if ($statement->columnCount() > 0) {
                    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $sets[] = $rows;
                    foreach ($rows as $row) {
                        foreach ($row as $key => $value) {
                            if (in_array(strtolower($key), ['message', 'mensaje'], true) && is_string($value)) {
                                $message = $value;
                            }
                        }
                    }
                }
            } while ($statement->nextRowset());
            $statement->closeCursor();
            $statement = null;

            return response()->json([
                'status' => 200,
                'message' => $message ?? 'Reincorporacion procesada correctamente.',
                'data' => $sets ? $sets[count($sets) - 1] : [],
                'resultados' => $sets,
            ]);
        } catch (PDOException $e) {
            // SIGNAL SQLSTATE 45000: devolver solo MESSAGE_TEXT, sin SQL ni conexion.
            if (($e->errorInfo[0] ?? (string) $e->getCode()) === '45000') {
                return response()->json([
                    'status' => 422,
                    'message' => $e->errorInfo[2] ?? 'El procedimiento rechazo la reincorporacion.',
                ], 422);
            }
            report($e);
            return response()->json(['status' => 500, 'message' => 'No fue posible procesar la reincorporacion.'], 500);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 500, 'message' => 'No fue posible procesar la reincorporacion.'], 500);
        } finally {
            if ($statement !== null) {
                try {
                    $statement->closeCursor();
                } catch (Throwable $cleanupError) {
                    report($cleanupError);
                }
            }
        }
    }
}
