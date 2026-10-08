<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ReincorporacionController extends Controller
{
    public function show($uid)
    {
        if (!ctype_digit((string) $uid) || (int) $uid < 1 || (float) $uid > 4294967295) {
            return response()->json(['status' => 422, 'message' => 'El uid debe ser un entero positivo válido.'], 422);
        }

        $persona = DB::table('persona')->where('uid', $uid)
            ->first(['uid', 'nombre', 'primerApellido', 'segundoApellido', 'curp']);
        $trayectorias = DB::table('alumno as a')
            ->leftJoin('carrera as c', function ($join) {
                $join->on('c.idNivel', '=', 'a.idNivel')->on('c.idCarrera', '=', 'a.idCarrera');
            })
            ->where('a.uid', $uid)->orderBy('a.secuencia')
            ->get(['a.secuencia', 'a.idNivel', 'a.idCarrera', 'a.matricula', 'a.activo', 'c.descripcion as carrera']);

        if (!$persona || $trayectorias->isEmpty()) {
            return response()->json(['status' => 404, 'message' => 'Alumno no encontrado.'], 404);
        }

        // Cualquier registro impide reincorporarse, incluso BloqueoActivo = 0.
        $bloqueos = DB::table('bloqueoPersonas')->where('uid', $uid)
            ->orderBy('secuencia')->orderBy('idBloqueo')->orderBy('secuenciaBloq')
            ->get(['secuencia', 'idBloqueo', 'BloqueoActivo', 'descripcion'])
            ->map(fn ($b) => [
                'secuencia' => (int) $b->secuencia,
                'idBloqueo' => (int) $b->idBloqueo,
                'activo' => (bool) $b->BloqueoActivo,
                'motivo' => trim((string) $b->descripcion) ?: 'Motivo no registrado',
            ])->values();

        $carreras = [];
        $totalCentavos = 0;
        $adeudoCompleto = true;
        foreach ($trayectorias as $a) {
            $ciclo = DB::table('ciclos as cl')
                ->leftJoin('periodo as p', function ($join) {
                    $join->on('p.idNivel', '=', 'cl.idNivel')->on('p.idPeriodo', '=', 'cl.idPeriodo');
                })
                ->leftJoin('tipoBaja as tb', 'tb.idTipoBaja', '=', 'cl.idTipoBaja')
                ->where('cl.uid', $uid)->where('cl.secuencia', $a->secuencia)->where('cl.idNivel', $a->idNivel)
                ->orderByDesc('cl.idPeriodo')->orderByDesc('cl.indexCiclo')
                ->first(['cl.idPeriodo', 'cl.semestre', 'cl.idTipoBaja', 'p.activo as periodoActivo', 'tb.descripcion as causaBaja']);

            $adeudo = null;
            if ($ciclo) {
                // Equivalente al saldo total del procedimiento saldo, sin sus escrituras
                // en bloqueoPersonas ni generación de recargos.
                $saldo = DB::table('edocta as e')
                    ->join('servicio as s', 's.idServicio', '=', 'e.idServicio')
                    ->where('s.tipoEdoCta', 1)->where('e.uid', $uid)
                    ->where('e.secuencia', $a->secuencia)->where('e.idPeriodo', $ciclo->idPeriodo)
                    ->selectRaw("COALESCE(SUM(CASE WHEN e.tipomovto = 'C' THEN COALESCE(e.importe, 0) WHEN e.tipomovto = 'A' THEN -COALESCE(e.importe, 0) ELSE 0 END), 0) AS saldo")
                    ->first()->saldo;
                // Un saldo a favor no reduce el adeudo de otras carreras.
                $centavos = max(0, (int) round((float) $saldo * 100));
                $totalCentavos += $centavos;
                $adeudo = $this->importe($centavos);
            } else {
                $adeudoCompleto = false;
            }

            $siguientePeriodo = $ciclo ? DB::table('periodo')
                ->where('idNivel', $a->idNivel)
                ->where('idPeriodo', '>=', $ciclo->idPeriodo)
                ->where('inscripciones', 1)
                ->orderBy('idPeriodo')
                ->value('idPeriodo') : null;

            $baja = (int) $a->activo === 0;
            $carreras[] = [
                'secuencia' => (int) $a->secuencia,
                'idNivel' => (int) $a->idNivel,
                'idCarrera' => (int) $a->idCarrera,
                'carrera' => $a->carrera,
                'matricula' => $a->matricula,
                'semestre' => $ciclo && $ciclo->semestre !== null ? (int) $ciclo->semestre : null,
                'idPeriodo' => $ciclo ? (int) $ciclo->idPeriodo : null,
                'periodoActivo' => $ciclo && $ciclo->periodoActivo !== null ? (bool) $ciclo->periodoActivo : null,
                'siguientePeriodoInscripcion' => $siguientePeriodo !== null ? (int) $siguientePeriodo : null,
                'baja' => $baja ? ($ciclo->causaBaja ?? 'Baja sin causa registrada') : 'Sin baja',
            ];
        }

        return response()->json([
            'status' => 200,
            'alumno' => [
                'uid' => (int) $persona->uid,
                'nombreCompleto' => implode(' ', array_filter(array_map(
                    fn ($parte) => trim((string) $parte),
                    [$persona->nombre, $persona->primerApellido, $persona->segundoApellido]
                ), fn ($parte) => $parte !== '')),
                'curp' => $persona->curp,
                'adeudoGlobal' => $adeudoCompleto ? $this->importe($totalCentavos) : null,
                'adeudoCompleto' => $adeudoCompleto,
                'bloqueo' => $bloqueos->isNotEmpty(),
                'mensajeBloqueo' => $bloqueos->isEmpty() ? 'Sin bloqueos' : 'Alumno con bloqueos',
                'puedeReincorporarse' => $bloqueos->isEmpty(),
                'carreras' => $carreras,
            ],
        ]);
    }

    private function importe(int $centavos): string
    {
        return intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
