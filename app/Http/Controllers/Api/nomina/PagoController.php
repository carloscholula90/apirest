<?php

namespace App\Http\Controllers\Api\nomina;

use App\Http\Controllers\Api\escolar\Concerns\ManejaHorarios;
use App\Http\Controllers\Controller;
use App\Models\escolar\DetalleTurno;
use App\Models\escolar\HorarioProfesor;
use App\Models\escolar\TipoBloque;
use App\Models\nomina\CalendarioPago;
use App\Models\nomina\DetallePago;
use App\Models\nomina\Pago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Calculo del pago de profesores por calendario (Notion, seccion 3).
 *
 * Horas trabajadas: por cada actividad del horario del profesor cuyo tipo de bloque es pagable,
 * se multiplica su duracion por las veces que ese dia de la semana cae dentro del calendario
 * (y dentro de la vigencia de la actividad). Solo cuentan actividades dentro del horario del turno.
 * Las horas son horas clase: cada bloque de 50 minutos cuenta como 1 hora.
 * Horas a descontar: todavia no hay fuente (asistencia), se registran en 0.
 * Honorarios = horas a pagar x costo por hora vigente del regimen al inicio del calendario.
 * Cada impuesto activo del regimen suma (traslado) o resta (retencion) honorarios x porcentaje.
 */
class PagoController extends Controller
{
    use ManejaHorarios;

    // Hora clase: un bloque de 50 minutos se paga como 1 hora
    const MINUTOS_POR_HORA = 50;

    // Antes de procesar devuelve el calculo como vista previa; despues, lo guardado
    public function concentrado($idCalendarioPago)
    {
        $calendario = CalendarioPago::find($idCalendarioPago);

        if (!$calendario) {
            return $this->returnEstatus('Calendario de pago no encontrado', 404, null);
        }

        if ($calendario->procesado) {
            return $this->returnData('concentrado', $this->concentradoGuardado($calendario), 200);
        }

        $calculo = $this->calcular($calendario);

        return response()->json([
            'concentrado' => array_map([$this, 'filaConcentrado'], $calculo['pagos']),
            'omitidos' => $calculo['omitidos'],
            'status' => 200,
        ], 200);
    }

    public function procesar($idCalendarioPago)
    {
        $calendario = CalendarioPago::find($idCalendarioPago);

        if (!$calendario) {
            return $this->returnEstatus('Calendario de pago no encontrado', 404, null);
        }

        if ($calendario->procesado) {
            return $this->returnEstatus('El calendario de pago ya fue procesado', 400, null);
        }

        $calculo = $this->calcular($calendario);

        if (!$calculo['pagos']) {
            return $this->returnEstatus('No hay empleados con horas pagables, regimen fiscal y costo por hora en este calendario', 400, $calculo['omitidos'] ?: null);
        }

        DB::transaction(function () use ($calendario, $calculo) {
            $ahora = now();

            foreach ($calculo['pagos'] as $p) {
                $pago = Pago::create([
                    'idCalendarioPago' => $calendario->idCalendarioPago,
                    'uid' => $p['uid'],
                    'idRegimenFiscal' => $p['idRegimenFiscal'],
                    'costoPorHora' => $p['costoHora'],
                    'totalHoras' => $p['horasTrabajadas'],
                    'horasADescontar' => $p['horasDescontar'],
                    'netoAPagar' => $p['neto'],
                    'fechaCreacion' => $ahora,
                ]);

                foreach ($p['detalles'] as $d) {
                    DetallePago::create([
                        'idPago' => $pago->idPago,
                        'idRegimenFiscalImpuesto' => $d['idRegimenFiscalImpuesto'],
                        'idImpuesto' => $d['idImpuesto'],
                        'descripcion' => $d['descripcion'],
                        'porcentaje' => $d['porcentaje'],
                        'esRetencion' => $d['esRetencion'],
                        'importe' => $d['importe'],
                        'fechaCreacion' => $ahora,
                    ]);
                }
            }

            $calendario->procesado = 1;
            $calendario->save();
        });

        return response()->json([
            'message' => 'Calendario de pago procesado',
            'pagos' => count($calculo['pagos']),
            'omitidos' => $calculo['omitidos'],
            'status' => 200,
        ], 200);
    }

    private function calcular(CalendarioPago $calendario)
    {
        $inicio = Carbon::parse($calendario->fechaInicio)->startOfDay();
        $fin = Carbon::parse($calendario->fechaFin)->startOfDay();

        // Horario del turno por dia (1 domingo ... 7 sabado)
        $rangos = [];
        foreach (DetalleTurno::where('idTurno', $calendario->idTurno)->get() as $detalle) {
            $dia = $this->diaNumero($detalle->diaSemana);
            if ($dia) {
                $rangos[$dia] = [$detalle->horaInicio, $detalle->horaFin];
            }
        }

        $tiposPagables = TipoBloque::where('pagable', 1)->pluck('idTipoBloque');

        $horarios = HorarioProfesor::whereIn('idTipoBloque', $tiposPagables)
            ->where('fechaInicio', '<=', $fin->toDateString())
            ->where('fechaFin', '>=', $inicio->toDateString())
            ->get();

        $horasPorProfesor = [];
        foreach ($horarios as $h) {
            $dia = $this->diaNumero($h->diaSemana);

            if (!$dia || !isset($rangos[$dia]) || !$this->horaDentroDe($h->horaInicio, $h->horaFin, $rangos[$dia][0], $rangos[$dia][1])) {
                continue;
            }

            $desde = $inicio->max(Carbon::parse($h->fechaInicio)->startOfDay());
            $hasta = $fin->min(Carbon::parse($h->fechaFin)->startOfDay());
            $veces = $this->vecesDia($dia, $desde, $hasta);

            if (!$veces) {
                continue;
            }

            $duracion = (strtotime($h->horaFin) - strtotime($h->horaInicio)) / 60 / self::MINUTOS_POR_HORA;
            $horasPorProfesor[$h->uid] = ($horasPorProfesor[$h->uid] ?? 0) + $duracion * $veces;
        }

        if (!$horasPorProfesor) {
            return ['pagos' => [], 'omitidos' => []];
        }

        $empleados = $this->datosEmpleados(array_keys($horasPorProfesor));
        $impuestosPorRegimen = [];
        $costosPorRegimen = [];
        $pagos = [];
        $omitidos = [];

        foreach ($horasPorProfesor as $uid => $horas) {
            $empleado = $empleados[$uid] ?? null;

            if (!$empleado) {
                $omitidos[] = ['uid' => $uid, 'nombre' => null, 'motivo' => 'No es un empleado activo'];
                continue;
            }

            if (!$empleado->idRegimenFiscalNomina) {
                $omitidos[] = ['uid' => $uid, 'nombre' => $empleado->nombreCompleto, 'motivo' => 'No tiene regimen fiscal asignado'];
                continue;
            }

            $idRegimen = $empleado->idRegimenFiscalNomina;

            if (!array_key_exists($idRegimen, $costosPorRegimen)) {
                $costosPorRegimen[$idRegimen] = DB::table('costoHoraRegimen')
                    ->where('idRegimenFiscal', $idRegimen)
                    ->where('vigenciaInicio', '<=', $inicio->toDateString())
                    ->where(function ($query) use ($inicio) {
                        $query->whereNull('vigenciaFin')->orWhere('vigenciaFin', '>=', $inicio->toDateString());
                    })
                    ->value('costoHora');
                $impuestosPorRegimen[$idRegimen] = DB::table('regimenFiscalImpuesto')
                    ->join('impuestos', 'impuestos.idImpuesto', '=', 'regimenFiscalImpuesto.idImpuesto')
                    ->where('regimenFiscalImpuesto.idRegimenFiscal', $idRegimen)
                    ->where('regimenFiscalImpuesto.activo', 1)
                    ->select('regimenFiscalImpuesto.idRegimenFiscalImpuesto', 'impuestos.idImpuesto', 'impuestos.descripcion', 'impuestos.porcentaje', 'impuestos.esRetencion')
                    ->get();
            }

            $costoHora = $costosPorRegimen[$idRegimen];

            if ($costoHora === null) {
                $omitidos[] = ['uid' => $uid, 'nombre' => $empleado->nombreCompleto, 'motivo' => 'Su regimen fiscal no tiene costo por hora vigente al ' . $inicio->format('d/m/Y')];
                continue;
            }

            $horas = round($horas, 2);
            $horasDescontar = 0;
            $honorarios = round(($horas - $horasDescontar) * $costoHora, 2);
            $neto = $honorarios;
            $detalles = [];

            foreach ($impuestosPorRegimen[$idRegimen] as $impuesto) {
                $importe = round($honorarios * $impuesto->porcentaje / 100, 2);
                $neto += $impuesto->esRetencion ? -$importe : $importe;
                $detalles[] = [
                    'idRegimenFiscalImpuesto' => $impuesto->idRegimenFiscalImpuesto,
                    'idImpuesto' => $impuesto->idImpuesto,
                    'descripcion' => trim($impuesto->descripcion),
                    'porcentaje' => $impuesto->porcentaje,
                    'esRetencion' => (int) $impuesto->esRetencion,
                    'importe' => $importe,
                ];
            }

            $pagos[] = [
                'uid' => $uid,
                'nombre' => $empleado->nombreCompleto,
                'grado' => $empleado->grado,
                'idRegimenFiscal' => $idRegimen,
                'regimen' => $empleado->regimen,
                'horasTrabajadas' => $horas,
                'horasDescontar' => $horasDescontar,
                'costoHora' => (float) $costoHora,
                'honorarios' => $honorarios,
                'neto' => round($neto, 2),
                'detalles' => $detalles,
            ];
        }

        usort($pagos, function ($a, $b) {
            return strcmp($a['nombre'], $b['nombre']);
        });

        return ['pagos' => $pagos, 'omitidos' => $omitidos];
    }

    private function concentradoGuardado(CalendarioPago $calendario)
    {
        $pagos = Pago::where('idCalendarioPago', $calendario->idCalendarioPago)->get();
        $empleados = $this->datosEmpleados($pagos->pluck('uid')->all());
        $regimenes = DB::table('regimenFiscalNomina')->pluck('descripcion', 'idRegimenFiscal');
        $detalles = DetallePago::whereIn('idPago', $pagos->pluck('idPago'))->get()->groupBy('idPago');

        $filas = $pagos->map(function ($pago) use ($empleados, $regimenes, $detalles) {
            $empleado = $empleados[$pago->uid] ?? null;

            return $this->filaConcentrado([
                'uid' => $pago->uid,
                'nombre' => $empleado ? $empleado->nombreCompleto : (string) $pago->uid,
                'grado' => $empleado ? $empleado->grado : null,
                'regimen' => $regimenes[$pago->idRegimenFiscal] ?? null,
                'horasTrabajadas' => (float) $pago->totalHoras,
                'horasDescontar' => (float) $pago->horasADescontar,
                'costoHora' => (float) $pago->costoPorHora,
                'honorarios' => round(($pago->totalHoras - $pago->horasADescontar) * $pago->costoPorHora, 2),
                'neto' => (float) $pago->netoAPagar,
                'detalles' => ($detalles[$pago->idPago] ?? collect())->map(function ($d) {
                    return $d->toArray();
                })->all(),
            ]);
        })->sortBy('nombre')->values();

        return $filas;
    }

    // Formato del reporte concentrado: IVA trasladado y retenciones de ISR e IVA por separado
    private function filaConcentrado(array $p)
    {
        $iva = 0;
        $retencionIsr = 0;
        $porcentajeIsr = 0;
        $retencionIva = 0;

        foreach ($p['detalles'] as $d) {
            if (!$d['esRetencion']) {
                $iva += $d['importe'];
            } elseif (stripos($d['descripcion'], 'ISR') !== false) {
                $retencionIsr += $d['importe'];
                $porcentajeIsr += $d['porcentaje'];
            } else {
                $retencionIva += $d['importe'];
            }
        }

        return [
            'uid' => $p['uid'],
            'nombre' => $p['nombre'],
            'grado' => $p['grado'],
            'regimen' => $p['regimen'],
            'horasTrabajadas' => $p['horasTrabajadas'],
            'horasDescontar' => $p['horasDescontar'],
            'costoHora' => $p['costoHora'],
            'honorarios' => $p['honorarios'],
            'iva' => round($iva, 2),
            'retencionIsr' => round($retencionIsr, 2),
            'porcentajeIsr' => (float) $porcentajeIsr,
            'retencionIva' => round($retencionIva, 2),
            'neto' => $p['neto'],
            'detalles' => $p['detalles'],
        ];
    }

    // Nombre (apellidos primero), grado y regimen de los empleados activos, indexados por uid
    private function datosEmpleados(array $uids)
    {
        if (!$uids) {
            return [];
        }

        $conGrado = Schema::hasColumn('empleado', 'gradoEstudios');

        $filas = DB::table('empleado')
            ->join('persona', 'persona.uid', '=', 'empleado.uid')
            ->leftJoin('regimenFiscalNomina', 'regimenFiscalNomina.idRegimenFiscal', '=', 'empleado.idRegimenFiscalNomina')
            ->whereIn('empleado.uid', $uids)
            ->whereNull('empleado.fechabaja')
            ->select(
                'empleado.uid',
                'empleado.idRegimenFiscalNomina',
                'regimenFiscalNomina.descripcion as regimen',
                'persona.nombre',
                'persona.primerApellido',
                'persona.segundoApellido',
                $conGrado ? 'empleado.gradoEstudios as grado' : DB::raw('NULL as grado')
            )
            ->orderByDesc('empleado.secuencia')
            ->get();

        $empleados = [];
        foreach ($filas as $fila) {
            if (isset($empleados[$fila->uid])) {
                continue;
            }
            $fila->nombreCompleto = mb_strtoupper(trim(preg_replace('/\s+/', ' ', $fila->primerApellido . ' ' . $fila->segundoApellido . ' ' . $fila->nombre)), 'UTF-8');
            $empleados[$fila->uid] = $fila;
        }

        return $empleados;
    }

    // Veces que el dia de la semana (1 domingo ... 7 sabado) cae entre dos fechas, inclusive
    private function vecesDia($dia, Carbon $desde, Carbon $hasta)
    {
        if ($desde->gt($hasta)) {
            return 0;
        }

        $primero = $desde->copy();
        while ($primero->dayOfWeek + 1 !== $dia) {
            $primero->addDay();
        }

        // Carbon 3 devuelve la diferencia como float
        return $primero->gt($hasta) ? 0 : intdiv((int) round(abs($primero->diffInDays($hasta))), 7) + 1;
    }
}
