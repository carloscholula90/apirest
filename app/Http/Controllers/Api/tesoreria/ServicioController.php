<?php

namespace App\Http\Controllers\Api\tesoreria;  
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class ServicioController extends Controller
{


public function index($uid, $matricula, $tipoEdoCta,$idPeriodo)
{
    // Validación básica de parámetros
    if (
        !ctype_digit((string) $uid)
        || !ctype_digit((string) $matricula)
        || !ctype_digit((string) $tipoEdoCta)
        || !ctype_digit((string) $idPeriodo)
    ) {
        abort(400, 'Parámetros inválidos');
    }

    $data = $this->condonacion($uid, $matricula, $tipoEdoCta, $idPeriodo);

    if ($data->isEmpty()) {
        return $data;
    }

    /*
     * De los servicios principales se devuelve solamente el primero,
     * respetando el orden aplicado por condonacion():
     *
     * 1. Traspaso de saldos
     * 2. Inscripcion
     * 3. Recargo
     * 4. Colegiatura
     *
     * Los servicios que no pertenecen a ninguno de esos cuatro grupos
     * se devuelven completos.
     */
    $configuracion = DB::table('configuracionTesoreria')
        ->where('idNivel', (int) $data->first()->idNivel)
        ->select([
            'idServicioTraspasoSaldos1',
            'idServicioInscripcion',
            'idServicioRecargo',
            'idServicioColegiatura',
        ])
        ->first();

    /*
     * Aplicación virtual del saldo anterior negativo.
     *
     * No se crea ni modifica ningún movimiento. El crédito únicamente reduce
     * los saldos disponibles para pagar, respetando el orden configurado para
     * el nivel y, para órdenes iguales, la parcialidad del cargo.
     */
    $idNivel = (int) $data->first()->idNivel;
    $secuencia = (int) $data->first()->secuencia;
    $idServicioSaldoAnterior =
        (int) ($configuracion->idServicioTraspasoSaldos1 ?? 0);

    $ordenesCobro = DB::table('ordenCobroServicio')
        ->where('idNivel', $idNivel)
        ->pluck('orden', 'idServicio');

    $data = $data
        ->sort(function ($a, $b) use ($ordenesCobro) {
            $ordenA = (int) ($ordenesCobro->get((int) $a->idServicio) ?? 999999);
            $ordenB = (int) ($ordenesCobro->get((int) $b->idServicio) ?? 999999);

            if ($ordenA !== $ordenB) {
                return $ordenA <=> $ordenB;
            }

            $parcialidadA = $a->parcialidad === null
                ? 999999
                : (int) $a->parcialidad;
            $parcialidadB = $b->parcialidad === null
                ? 999999
                : (int) $b->parcialidad;

            if ($parcialidadA !== $parcialidadB) {
                return $parcialidadA <=> $parcialidadB;
            }

            return (int) $a->idServicio <=> (int) $b->idServicio;
        })
        ->values();

    $creditoSaldoAnterior = 0.0;

    if ($idServicioSaldoAnterior > 0) {
        $saldoAnterior = DB::table('edocta')
            ->where('uid', (int) $uid)
            ->where('secuencia', $secuencia)
            ->where('idPeriodo', (int) $idPeriodo)
            ->where('idServicio', $idServicioSaldoAnterior)
            ->selectRaw("\n                SUM(\n                    CASE\n                        WHEN tipomovto = 'C' THEN importe\n                        WHEN tipomovto = 'A' THEN -importe\n                        ELSE 0\n                    END\n                ) AS saldo\n            ")
            ->value('saldo');

        $saldoAnterior = round((float) ($saldoAnterior ?? 0), 2);
        $creditoSaldoAnterior = $saldoAnterior < 0
            ? abs($saldoAnterior)
            : 0.0;
    }

    if ($creditoSaldoAnterior > 0) {
        foreach ($data as $servicio) {
            if ((int) $servicio->idServicio === $idServicioSaldoAnterior) {
                continue;
            }

            $saldoServicio = round((float) $servicio->monto, 2);
            $creditoAplicado = min($creditoSaldoAnterior, $saldoServicio);

            $servicio->monto = round($saldoServicio - $creditoAplicado, 2);
            $creditoSaldoAnterior = round(
                $creditoSaldoAnterior - $creditoAplicado,
                2
            );

            if ($creditoSaldoAnterior <= 0) {
                break;
            }
        }

        $data = $data
            ->filter(fn ($servicio) => round((float) $servicio->monto, 2) > 0)
            ->values();
    }

    if ($data->isEmpty()) {
        return $data;
    }

    $idsServiciosPrincipales = collect([
        $configuracion->idServicioTraspasoSaldos1 ?? null,
        $configuracion->idServicioInscripcion ?? null,
        $configuracion->idServicioRecargo ?? null,
        $configuracion->idServicioColegiatura ?? null,
    ])
        ->filter(fn ($idServicio) => $idServicio !== null)
        ->map(fn ($idServicio) => (int) $idServicio)
        ->unique()
        ->values();

    $primerServicioPrincipal = $data->first(function ($servicio) use ($idsServiciosPrincipales) {
        return $idsServiciosPrincipales->contains((int) $servicio->idServicio);
    });

    $serviciosAdicionales = $data
        ->reject(function ($servicio) use ($idsServiciosPrincipales) {
            return $idsServiciosPrincipales->contains((int) $servicio->idServicio);
        })
        ->values();

    return collect($primerServicioPrincipal ? [$primerServicioPrincipal] : [])
        ->concat($serviciosAdicionales)
        ->values();
}

public function condonacion($uid, $matricula, $tipoEdoCta, $idPeriodo)
{
    foreach ([$uid, $matricula, $tipoEdoCta, $idPeriodo] as $parametro) {
        if (!ctype_digit((string) $parametro)) {
            abort(400, 'Parámetros inválidos');
        }
    }

    $descripcionServicio = "GROUP_CONCAT(DISTINCT CONCAT(
        s.descripcion,
        ' ',
        CASE
            WHEN ct.idServicioColegiatura = s.idServicio
                OR ct.idServicioRecargo = s.idServicio
            THEN CASE CONVERT(SUBSTRING(cta.referencia, 4), UNSIGNED)
                WHEN 1 THEN 'ENERO'
                WHEN 2 THEN 'FEBRERO'
                WHEN 3 THEN 'MARZO'
                WHEN 4 THEN 'ABRIL'
                WHEN 5 THEN 'MAYO'
                WHEN 6 THEN 'JUNIO'
                WHEN 7 THEN 'JULIO'
                WHEN 8 THEN 'AGOSTO'
                WHEN 9 THEN 'SEPTIEMBRE'
                WHEN 10 THEN 'OCTUBRE'
                WHEN 11 THEN 'NOVIEMBRE'
                WHEN 12 THEN 'DICIEMBRE'
                ELSE ''
            END
            ELSE ''
        END
    ) ORDER BY s.descripcion SEPARATOR ' + ')";

    return DB::table('edocta as cta')
        ->join('alumno as al', function ($join) {
            $join->on('al.uid', '=', 'cta.uid')
                ->on('al.secuencia', '=', 'cta.secuencia');
        })
        ->join('periodo as per', function ($join) {
            $join->on('per.idNivel', '=', 'al.idNivel')
                ->on('per.idPeriodo', '=', 'cta.idPeriodo');
        })
        ->join('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
        ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
        ->join('configuracionTesoreria as ct', 'ct.idNivel', '=', 'al.idNivel')
        ->where('al.uid', (int) $uid)
        ->where('al.matricula', $matricula)
        ->where('s.tipoEdoCta', (int) $tipoEdoCta)
        ->where('cta.idPeriodo', (int) $idPeriodo)
        ->select([
            'niv.idNivel',
            'niv.descripcion as nivel',
            's.efectivo',
            's.tarjeta',
            'per.idPeriodo',
            's.idServicio',
            's.tipoEdoCta',
            'cta.uid',
            'al.matricula',
            'cta.parcialidad',
            'cta.secuencia',
            's.cargoAutomatico as cargoAut',
        ])
        ->selectRaw("{$descripcionServicio} AS servicios")
        ->selectRaw("{$descripcionServicio} AS servicio")
        ->selectRaw("SUM(
            CASE
                WHEN cta.tipomovto = 'C' THEN cta.importe
                WHEN cta.tipomovto = 'A' THEN -cta.importe
                ELSE 0
            END
        ) AS monto")
        ->selectRaw("MAX(
            CASE WHEN cta.tipomovto = 'C' THEN cta.fechaVencimiento END
        ) AS fechaVencimiento")
        ->selectRaw("MAX(
            CASE WHEN cta.tipomovto = 'C' THEN cta.consecutivo END
        ) AS consecutivo")
        ->groupBy([
            'niv.idNivel',
            'niv.descripcion',
            's.efectivo',
            's.tarjeta',
            'per.idPeriodo',
            's.idServicio',
            's.tipoEdoCta',
            'cta.uid',
            'al.matricula',
            'cta.parcialidad',
            'cta.secuencia',
            's.cargoAutomatico',
            'ct.idServicioTraspasoSaldos1',
            'ct.idServicioInscripcion',
            'ct.idServicioRecargo',
            'ct.idServicioColegiatura',
        ])
        ->havingRaw("SUM(
            CASE
                WHEN cta.tipomovto = 'C' THEN cta.importe
                WHEN cta.tipomovto = 'A' THEN -cta.importe
                ELSE 0
            END
        ) > 0")
        ->orderBy('al.matricula')
        ->orderByRaw('CASE
            WHEN s.idServicio = ct.idServicioTraspasoSaldos1 THEN 1
            WHEN s.idServicio = ct.idServicioInscripcion THEN 2
            WHEN s.idServicio = ct.idServicioRecargo THEN 3
            WHEN s.idServicio = ct.idServicioColegiatura THEN 4
            ELSE 5
        END')
        ->orderByRaw("MAX(
            CASE WHEN cta.tipomovto = 'C' THEN cta.fechaVencimiento END
        )")
        ->get();
}

public function store(Request $request){

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Beca::max('idBeca');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $becas = Beca::create([
                            'idBeca' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion)),
                            'aplicaInscripcion' => $request->aplicaInscripcion,
                            'aplicaColegiatura' => $request->aplicaColegiatura,
                            'fechaAlta' => Carbon::now(),
                            'fechaModificacion' => Carbon::now()
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('La Beca ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar la Beca',400,null);
        }

        if (!$becas) 
            return $this->returnEstatus('Error al crear la Beca',500,null); 
        return $this->returnData('becas',$becas,200);   
    }


public function condonar(Request $request)
{
    $data = $request->validate([
        'movimientos' => 'required|array|min:1',
        'movimientos.*.uid' => 'required|integer',
        'movimientos.*.secuencia' => 'required|integer',
        'movimientos.*.idServicio' => 'required|integer',
        'movimientos.*.consecutivo' => 'required|integer',
        'movimientos.*.idPeriodo' => 'required|integer',
        'movimientos.*.uidcajero' => 'required|integer',
    ]);

    $fecha = Carbon::now('America/Mexico_City')->format('Y-m-d');

    DB::beginTransaction();

    try {
        $uid = null;
        $secuencia = null;
        $idPeriodo = null;
        $uidcajero = null;

        foreach ($data['movimientos'] as $movimiento) {
            DB::table('edocta')
                ->where('uid', $movimiento['uid'])
                ->where('secuencia', $movimiento['secuencia'])
                ->where('idServicio', $movimiento['idServicio'])
                ->where('consecutivo', $movimiento['consecutivo'])
                ->where('idPeriodo', $movimiento['idPeriodo'])
                ->update([
                    'importe' => 0,
                    'fechaMovto' => $fecha,
                    'uidcajero' => $movimiento['uidcajero']
                ]);

            $uid = $movimiento['uid'];
            $secuencia = $movimiento['secuencia'];
            $idPeriodo = $movimiento['idPeriodo'];
            $uidcajero = $movimiento['uidcajero'];
        }

        // Obtener matrícula
        $matricula = DB::table('alumno')
            ->where('uid', $uid)
            ->where('secuencia', $secuencia)
            ->value('matricula');

        if (!$matricula) {
            DB::rollBack();

            return $this->returnEstatus(
                'No se encontró la matrícula del alumno',
                404,
                'Matrícula no encontrada'
            );
        }

        // Recalcular saldo
        DB::statement("CALL saldo(?, ?, ?, @vencido, @total)", [
            $uid,
            $matricula,
            $idPeriodo
        ]);

        $saldoResult = DB::select("SELECT @vencido AS vencido, @total AS total");

        $vencido = $saldoResult[0]->vencido ?? 0;

        if ($vencido > 0) {
            $existe = DB::table('bloqueoPersonas')
                ->where('uid', $uid)
                ->where('secuencia', $secuencia)
                ->where('idBloqueo', 1)
                ->exists();

            if (!$existe) {
                DB::table('bloqueoPersonas')->insert([
                    'uid' => $uid,
                    'secuencia' => $secuencia,
                    'idBloqueo' => 1,
                    'uidBloqueador' => $uidcajero,
                    'secuenciaBloq' => 1,
                    'BloqueoActivo' => '1',
                    'fechaBloqueo' => Carbon::now('America/Mexico_City'),
                    'descripcion' => 'Adeudo'
                ]);
            }
        } else {
            DB::table('bloqueoPersonas')
                ->where('uid', $uid)
                ->where('secuencia', $secuencia)
                ->where('idBloqueo', 1)
                ->delete();
        }

        DB::commit();

        return $this->returnData('mensaje', 'Condonación exitosa', 200);

    } catch (\Throwable $e) {
        DB::rollBack();

        return $this->returnEstatus(
            'Error al actualizar el registro',
            500,
            $e->getMessage()
        );
    }
}    
  }
