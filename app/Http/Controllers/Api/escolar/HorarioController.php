<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Api\escolar\Concerns\ManejaHorarios;
use App\Http\Controllers\Controller;
use App\Models\escolar\DetalleTurno;
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

    // Borra solo las sesiones del periodo que no se han impartido
    public function destroy($grupoSec)
    {
        $grupo = $this->buscaGrupo($grupoSec);

        if (!$grupo) {
            return $this->returnEstatus('Grupo no encontrado', 404, null);
        }

        $periodo = $this->periodoDe($grupo);

        if (!$periodo || !$periodo->fechaInicio || !$periodo->fechaTermino) {
            return $this->returnEstatus('El periodo del grupo no tiene fechas de inicio y termino', 400, null);
        }

        try {
            $this->sesionesPendientes($grupoSec, $periodo)->delete();
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

        $periodo = $this->periodoDe($grupo);

        if (!$periodo || !$periodo->fechaInicio || !$periodo->fechaTermino) {
            return 'El periodo del grupo no tiene fechas de inicio y termino';
        }

        $horarios = [];
        $rangos = $this->rangosTurno($grupo->idTurno, $grupo->idNivel);
        $bloquesTurno = $this->bloquesTurno($grupo->idTurno);

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

            // Con el turno configurado en dtlTurno, la clase debe caer en sus dias y dentro de su horario
            if ($rangos) {
                if (!isset($rangos[$numero])) {
                    return 'El turno del grupo no tiene clases el '.$this->diaNombre($numero);
                }

                $fin = $inicio->copy()->addMinutes($minutos)->format('H:i');

                if (!$this->horaDentroDe($dia['horaIni'], $fin, $rangos[$numero][0], $rangos[$numero][1])) {
                    return 'La clase del '.$this->diaNombre($numero).' debe quedar entre '.substr($rangos[$numero][0], 0, 5).' y '.substr($rangos[$numero][1], 0, 5).', el horario del turno';
                }
            }

            // Los bloques del turno (defaultTurno, como el receso) no se pueden ocupar con clase
            $fin = $inicio->copy()->addMinutes($minutos)->format('H:i');

            foreach ($bloquesTurno as $bloque) {
                if ($dia['horaIni'] < $bloque['horaFin'] && $fin > $bloque['horaIni']) {
                    return 'La clase del '.$this->diaNombre($numero).' se traslapa con '.$bloque['descripcion'].' del turno ('.$bloque['horaIni'].' - '.$bloque['horaFin'].')';
                }
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

        $traslape = $this->buscaTraslape($grupo, $sesiones);

        if ($traslape) {
            return $traslape;
        }

        return ['grupo' => $grupo, 'periodo' => $periodo, 'sesiones' => $sesiones];
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

                $this->sesionesPendientes($grupoSec, $plan['periodo'])->delete();

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
            'bloquesTurno' => $this->bloquesTurno($grupo->idTurno),
            'ocupados' => $this->ocupados($grupo),
        ];
    }

    // Bloques activos del turno (defaultTurno) con la descripcion y color de su tipo de bloque
    private function bloquesTurno($idTurno)
    {
        return DB::table('defaultTurno as d')
            ->leftJoin('tipoBloque as t', 't.idTipoBloque', '=', 'd.idTipoBloque')
            ->where('d.idTurno', $idTurno)
            ->where('d.activo', 1)
            ->orderBy('d.horaInicio')
            ->get(['d.horaInicio', 'd.horaFin', 'd.idTipoBloque', 't.descripcion', 't.color'])
            ->map(function ($bloque) {
                return [
                    'horaIni' => substr($bloque->horaInicio, 0, 5),
                    'horaFin' => substr($bloque->horaFin, 0, 5),
                    'idTipoBloque' => (int) $bloque->idTipoBloque,
                    'descripcion' => trim((string) $bloque->descripcion) ?: 'BLOQUE',
                    'color' => trim((string) $bloque->color),
                ];
            })
            ->all();
    }

    /*
     * Otros grupos con los que no se puede traslapar: las demas asignaturas del mismo grupo
     * (nivel, periodo y clave de grupo) y los grupos del mismo profesor, de cualquier nivel o periodo.
     * Indexados por gruposec, con tipo "grupo" o "profesor".
     */
    private function relacionados($grupo)
    {
        $grupoSec = $grupo->gruposec ?? $grupo->grupoSec;

        $filas = DB::table('grupos as g')
            ->leftJoin('asignatura as a', 'a.idAsignatura', '=', 'g.idAsignatura')
            ->where('g.gruposec', '<>', $grupoSec)
            ->where(function ($query) use ($grupo) {
                $query->where(function ($mismo) use ($grupo) {
                    $mismo->where('g.idNivel', $grupo->idNivel)
                        ->where('g.idPeriodo', $grupo->idPeriodo)
                        ->where('g.grupo', $grupo->grupo);
                });

                if ($grupo->uidProfesor) {
                    $query->orWhere('g.uidProfesor', $grupo->uidProfesor);
                }
            })
            ->get(['g.gruposec as gs', 'g.idNivel', 'g.idPeriodo', 'g.grupo', 'g.idAsignatura', 'a.descripcion as asignatura']);

        $relacionados = [];

        foreach ($filas as $fila) {
            $mismoGrupo = $fila->idNivel == $grupo->idNivel && $fila->idPeriodo == $grupo->idPeriodo && $fila->grupo === $grupo->grupo;
            $relacionados[$fila->gs] = [
                'tipo' => $mismoGrupo ? 'grupo' : 'profesor',
                'grupoSec' => (int) $fila->gs,
                'grupo' => trim($fila->grupo),
                'idAsignatura' => trim($fila->idAsignatura),
                'asignatura' => trim((string) $fila->asignatura),
            ];
        }

        return $relacionados;
    }

    // Horario por dia de los grupos relacionados dentro del periodo, para mostrarlo en la cuadricula
    private function ocupados($grupo)
    {
        $periodo = $this->periodoDe($grupo);
        $relacionados = $this->relacionados($grupo);

        if (!$periodo || !$periodo->fechaInicio || !$periodo->fechaTermino || !$relacionados) {
            return [];
        }

        $sesiones = Horario::whereIn('grupoSec', array_keys($relacionados))
            ->where('fechaIni', '<=', substr($periodo->fechaTermino, 0, 10).' 23:59:59')
            ->where('fechaFin', '>=', substr($periodo->fechaInicio, 0, 10).' 00:00:00')
            ->orderBy('fechaIni')
            ->get(['grupoSec', 'fechaIni', 'fechaFin', 'day']);

        // Como en el resumen propio, cada grupo y dia conserva el horario de su sesion mas reciente
        $ocupados = [];

        foreach ($sesiones as $sesion) {
            $ocupados[$sesion->grupoSec.'-'.$sesion->day] = $relacionados[$sesion->grupoSec] + [
                'day' => (int) $sesion->day,
                'horaIni' => Carbon::parse($sesion->fechaIni)->format('H:i'),
                'horaFin' => Carbon::parse($sesion->fechaFin)->format('H:i'),
            ];
        }

        return array_values($ocupados);
    }

    // Compara fecha por fecha las sesiones planeadas contra las de los grupos relacionados
    private function buscaTraslape($grupo, array $sesiones)
    {
        $relacionados = $this->relacionados($grupo);

        if (!$relacionados) {
            return null;
        }

        $existentes = Horario::whereIn('grupoSec', array_keys($relacionados))
            ->where('fechaIni', '<', max(array_column($sesiones, 'fechaFin')))
            ->where('fechaFin', '>', min(array_column($sesiones, 'fechaIni')))
            ->get(['grupoSec', 'fechaIni', 'fechaFin'])
            ->groupBy(function ($sesion) {
                return substr($sesion->fechaIni, 0, 10);
            });

        foreach ($sesiones as $sesion) {
            foreach ($existentes->get(substr($sesion['fechaIni'], 0, 10), []) as $otra) {
                if ($otra->fechaIni < $sesion['fechaFin'] && $otra->fechaFin > $sesion['fechaIni']) {
                    $inicio = Carbon::parse($sesion['fechaIni']);
                    $cuando = 'el '.$this->diaNombre($sesion['day']).' '.$inicio->format('d/m/Y')
                        .' ('.$inicio->format('H:i').' - '.Carbon::parse($sesion['fechaFin'])->format('H:i').')';
                    $r = $relacionados[$otra->grupoSec];

                    return $r['tipo'] === 'grupo'
                        ? 'La clase '.$cuando.' se traslapa con '.$r['idAsignatura'].' del mismo grupo '.$r['grupo']
                        : 'El profesor ya tiene clase '.$cuando.' en el grupo '.$r['grupo'].' ('.$r['idAsignatura'].')';
                }
            }
        }

        return null;
    }

    private function buscaGrupo($grupoSec)
    {
        return DB::table('grupos')->where('gruposec', $grupoSec)->first();
    }

    private function sesionesImpartidas($grupoSec)
    {
        return Horario::where('grupoSec', $grupoSec)->where('impartido', 1);
    }

    // Hora de inicio y fin del turno por dia (1 domingo ... 7 sabado) para el nivel del grupo;
    // vacio si ni el nivel ni los dias generales del turno estan configurados
    private function rangosTurno($idTurno, $idNivel)
    {
        $rangos = [];

        foreach (DetalleTurno::paraNivel($idTurno, $idNivel) as $detalle) {
            $numero = $this->diaNumero($detalle->diaSemana);

            if ($numero) {
                $rangos[$numero] = [$detalle->horaInicio, $detalle->horaFin];
            }
        }

        return $rangos;
    }

    private function periodoDe($grupo)
    {
        return DB::table('periodo')
            ->where('idNivel', $grupo->idNivel)
            ->where('idPeriodo', $grupo->idPeriodo)
            ->first();
    }

    // Sesiones que se pueden borrar: no impartidas y dentro de las fechas del periodo
    private function sesionesPendientes($grupoSec, $periodo)
    {
        return Horario::where('grupoSec', $grupoSec)
            ->whereBetween('fechaIni', [
                substr($periodo->fechaInicio, 0, 10).' 00:00:00',
                substr($periodo->fechaTermino, 0, 10).' 23:59:59',
            ])
            ->where(function ($query) {
                $query->whereNull('impartido')->orWhere('impartido', '<>', 1);
            });
    }
}
