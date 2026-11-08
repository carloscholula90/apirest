<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Api\escolar\Concerns\ManejaHorarios;
use App\Http\Controllers\Controller;
use App\Models\escolar\DuracionSesion;
use App\Models\escolar\Horario;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HorarioController extends Controller
{
    use ManejaHorarios;

    // Horario por dia y sesiones guardadas del grupo
    public function show($grupoSec)
    {
        $grupo = $this->buscaGrupo($grupoSec);

        if (!$grupo) {
            return $this->returnEstatus('Grupo no encontrado', 404, null);
        }

        return $this->returnData('horario', $this->resumen($grupo), 200);
    }

    // Calcula las sesiones sin guardar nada
    public function vistaPrevia(Request $request)
    {
        $plan = $this->planeaSesiones($request, $request->grupoSec);

        if (is_string($plan)) {
            return $this->returnEstatus($plan, 400, null);
        }

        return $this->returnData('sesiones', array_values(array_map(function ($sesion) {
            return [
                'fechaIni' => $sesion['fechaIni'],
                'fechaFin' => $sesion['fechaFin'],
                'day' => $sesion['day'],
            ];
        }, $plan['sesiones'])), 200);
    }

    public function store(Request $request)
    {
        $plan = $this->planeaSesiones($request, $request->grupoSec);

        if (is_string($plan)) {
            return $this->returnEstatus($plan, 400, null);
        }

        if (Horario::where('grupoSec', $request->grupoSec)->exists()) {
            return $this->returnEstatus('El grupo ya tiene horario', 400, null);
        }

        return $this->guardaSesiones($plan);
    }

    // Reemplaza las sesiones pendientes; las impartidas no se tocan
    public function update(Request $request, $grupoSec)
    {
        $plan = $this->planeaSesiones($request, $grupoSec);

        if (is_string($plan)) {
            return $this->returnEstatus($plan, 400, null);
        }

        return $this->guardaSesiones($plan);
    }

    public function destroy($grupoSec)
    {
        if (!$this->buscaGrupo($grupoSec)) {
            return $this->returnEstatus('Grupo no encontrado', 404, null);
        }

        try {
            $this->sesionesPendientes($grupoSec)->delete();
        } catch (QueryException $e) {
            return $this->returnEstatus('Error al eliminar el horario', 500, null);
        }

        $impartidas = $this->sesionesImpartidas($grupoSec)->count();

        return $this->returnEstatus(
            $impartidas ? 'Horario eliminado; se conservaron '.$impartidas.' sesiones impartidas' : 'Horario eliminado',
            200,
            null
        );
    }

    // Devuelve el grupo y sus sesiones calculadas, o un texto con el error
    private function planeaSesiones(Request $request, $grupoSec)
    {
        $validator = Validator::make($request->all(), [
            'dias' => 'required|array|min:1',
            'dias.*.day' => 'required',
            'dias.*.horaIni' => 'required|date_format:H:i',
            'dias.*.sesiones' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return 'Captura dia, hora de inicio y numero de sesiones de cada horario';
        }

        $grupo = $grupoSec ? $this->buscaGrupo($grupoSec) : null;

        if (!$grupo) {
            return 'Grupo no encontrado';
        }

        $duracion = DuracionSesion::minutos($grupo->idNivel, $grupo->idTurno);

        if (!$duracion) {
            return 'No hay duracion de sesion configurada para el nivel y turno del grupo';
        }

        $periodo = DB::table('periodo')
            ->where('idNivel', $grupo->idNivel)
            ->where('idPeriodo', $grupo->idPeriodo)
            ->first();

        if (!$periodo || !$periodo->fechaInicio || !$periodo->fechaTermino) {
            return 'El periodo del grupo no tiene fechas de inicio y termino';
        }

        $horarios = [];

        foreach ($request->dias as $dia) {
            $numero = $this->diaNumero($dia['day']);

            if (!$numero) {
                return 'El dia de la semana no es valido';
            }

            if (isset($horarios[$numero])) {
                return 'Cada dia solo puede tener un horario';
            }

            $minutos = (int) $dia['sesiones'] * $duracion;
            $inicio = Carbon::createFromFormat('H:i', $dia['horaIni']);

            if ($inicio->copy()->addMinutes($minutos)->format('Y-m-d') !== $inicio->format('Y-m-d')) {
                return 'La clase del '.$this->diaNombre($numero).' debe terminar antes de medianoche';
            }

            $horarios[$numero] = ['horaIni' => $dia['horaIni'], 'minutos' => $minutos];
        }

        $sesiones = [];
        $termino = Carbon::parse($periodo->fechaTermino)->startOfDay();

        $festivos = DB::table('diasFestivos')
            ->whereBetween('fechaFestivo', [$periodo->fechaInicio, $periodo->fechaTermino])
            ->pluck('fechaFestivo')
            ->map(function ($fecha) {
                return substr($fecha, 0, 10);
            })
            ->all();

        for ($fecha = Carbon::parse($periodo->fechaInicio)->startOfDay(); $fecha->lte($termino); $fecha->addDay()) {
            $numero = $fecha->dayOfWeek + 1;

            if (!isset($horarios[$numero]) || in_array($fecha->format('Y-m-d'), $festivos)) {
                continue;
            }

            $inicio = Carbon::parse($fecha->format('Y-m-d').' '.$horarios[$numero]['horaIni']);

            $sesiones[] = [
                'grupoSec' => $grupoSec,
                'fechaIni' => $inicio->format('Y-m-d H:i:s'),
                'fechaFin' => $inicio->copy()->addMinutes($horarios[$numero]['minutos'])->format('Y-m-d H:i:s'),
                'day' => $numero,
                'impartido' => 0,
            ];
        }

        if (!$sesiones) {
            return 'Ninguna fecha habil del periodo coincide con los dias capturados';
        }

        return ['grupo' => $grupo, 'sesiones' => $sesiones];
    }

    private function guardaSesiones(array $plan)
    {
        $grupo = $plan['grupo'];
        $grupoSec = $grupo->gruposec ?? $grupo->grupoSec;

        try {
            DB::transaction(function () use ($plan, $grupoSec) {
                $fechasImpartidas = $this->sesionesImpartidas($grupoSec)->pluck('fechaIni')
                    ->map(function ($fecha) {
                        return substr($fecha, 0, 10);
                    })
                    ->all();

                $this->sesionesPendientes($grupoSec)->delete();

                $nuevas = array_filter($plan['sesiones'], function ($sesion) use ($fechasImpartidas) {
                    return !in_array(substr($sesion['fechaIni'], 0, 10), $fechasImpartidas);
                });

                foreach (array_chunk($nuevas, 200) as $bloque) {
                    Horario::insert($bloque);
                }
            });
        } catch (QueryException $e) {
            return $this->returnEstatus('Error al guardar el horario', 500, null);
        }

        return $this->returnData('horario', $this->resumen($grupo), 200);
    }

    private function resumen($grupo)
    {
        $grupoSec = $grupo->gruposec ?? $grupo->grupoSec;
        $duracion = DuracionSesion::minutos($grupo->idNivel, $grupo->idTurno);

        $sesiones = Horario::where('grupoSec', $grupoSec)
            ->orderBy('fechaIni')
            ->get(['secHorarios', 'fechaIni', 'fechaFin', 'day', 'impartido']);

        // Cada dia conserva el horario de su sesion mas reciente
        $dias = [];

        foreach ($sesiones as $sesion) {
            $inicio = Carbon::parse($sesion->fechaIni);
            $fin = Carbon::parse($sesion->fechaFin);

            $dias[$sesion->day] = [
                'day' => (int) $sesion->day,
                'dia' => $this->diaNombre($sesion->day),
                'horaIni' => $inicio->format('H:i'),
                'horaFin' => $fin->format('H:i'),
                'sesiones' => $duracion ? (int) floor($inicio->diffInMinutes($fin) / $duracion) : null,
            ];
        }

        ksort($dias);

        return [
            'grupoSec' => (int) $grupoSec,
            'duracionSesion' => $duracion,
            'dias' => array_values($dias),
            'total' => $sesiones->count(),
            'impartidas' => $sesiones->where('impartido', 1)->count(),
            'sesiones' => $sesiones,
        ];
    }

    private function buscaGrupo($grupoSec)
    {
        return DB::table('grupos')->where('gruposec', $grupoSec)->first();
    }

    private function sesionesImpartidas($grupoSec)
    {
        return Horario::where('grupoSec', $grupoSec)->where('impartido', 1);
    }

    private function sesionesPendientes($grupoSec)
    {
        return Horario::where('grupoSec', $grupoSec)->where(function ($query) {
            $query->whereNull('impartido')->orWhere('impartido', '<>', 1);
        });
    }
}
