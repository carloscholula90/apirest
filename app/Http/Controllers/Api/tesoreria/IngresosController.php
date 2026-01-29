<?php

namespace App\Http\Controllers\Api\tesoreria;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF;
use App\Http\Controllers\Api\serviciosGenerales\GenericExport;
use Maatwebsite\Excel\Facades\Excel;

class IngresosController extends Controller
{
    // ===============================
    // PDF
    // ===============================
    public function index($concentrado, $inicio, $fin, $idCajero = null, $idCarrera = null)
    {
        return $this->generaReporte($concentrado, $inicio, $fin, $idCajero, $idCarrera, false);
    }

    // ===============================
    // EXCEL
    // ===============================
    public function indexExcel($concentrado, $inicio, $fin, $idCajero = null, $idCarrera = null)
    {
        return $this->generaReporte($concentrado, $inicio, $fin, $idCajero, $idCarrera, true);
    }

    // =====================================================
    // MÉTODO GENERAL
    // =====================================================
    private function generaReporte($concentrado, $inicio, $fin, $idCajero, $idCarrera, $excel)
    {
        $activo = DB::table('configuracion')
            ->where('id_campo', 1)
            ->value('valor') ?? 0;

        /*
        =====================================================
        1️⃣ ANALÍTICO (CORTE POR FORMA DE PAGO)
        =====================================================
        */
        if ($concentrado === 'N') {

            $query = DB::table('edocta as cta')
                ->select(
                    'cta.FechaPago',
                    'cta.uidcajero',
                    'ca.descripcion as carrera',
                    'p.descripcion as periodo',
                    'cta.idServicio',
                    'cta.uid',
                    'pers.nombre',
                    'pers.primerApellido',
                    'pers.segundoApellido',
                    'cta.importe',
                    'fp.descripcion as formadep'
                )
                ->join('persona as pers', 'pers.uid', '=', 'cta.uid')
                ->join('formaPago as fp', 'fp.idFormaPago', '=', 'cta.idformaPago')
                ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
                ->join('alumno as al', function ($j) {
                    $j->on('al.uid', '=', 'cta.uid')
                      ->on('al.secuencia', '=', 'cta.secuencia');
                })
                ->join('periodo as p', function ($j) {
                    $j->on('p.idPeriodo', '=', 'cta.idPeriodo')
                      ->on('p.idNivel', '=', 'al.idNivel');
                })
                ->join('carrera as ca', function ($j) {
                    $j->on('ca.idNivel', '=', 'al.idNivel')
                      ->on('ca.idCarrera', '=', 'al.idCarrera');
                })
                ->whereBetween('cta.FechaPago', [$inicio, $fin])
                ->where('cta.tipomovto', 'A')
                ->orderBy('fp.descripcion');

            if ($idCajero > 0) $query->where('cta.uidcajero', $idCajero);
            if ($idCarrera > 0) $query->where('ca.idCarrera', $idCarrera);
            if ($activo == 0) $query->where('s.tipoEdoCta', 1);

            $results = $query->get();
            if ($results->isEmpty()) {
                return response()->json(['status' => 500, 'message' => 'No hay registros']);
            }

            // ===== DATA + CORTES =====
            $dataExcel = [];
            $cutRows = [];
            $rowExcel = 2;

            $formaPagoActual = null;
            $totalFormaPago = 0;

            foreach ($results as $r) {
                $row = (array)$r;

                if ($formaPagoActual !== null && $formaPagoActual !== $row['formadep']) {
                    $dataExcel[] = [
                        'FechaPago' => 'TOTAL ' . $formaPagoActual,
                        'importe'   => $totalFormaPago
                    ];
                    $cutRows[] = $rowExcel++;
                    $totalFormaPago = 0;
                }

                $dataExcel[] = $row;
                $rowExcel++;

                $totalFormaPago += $row['importe'];
                $formaPagoActual = $row['formadep'];
            }

            // último total
            $dataExcel[] = [
                'FechaPago' => 'TOTAL ' . $formaPagoActual,
                'importe'   => $totalFormaPago
            ];
            $cutRows[] = $rowExcel++;

            $headers = [
                'FECHA','CAJERO','CARRERA','PERIODO','SERVICIO',
                'UID','NOMBRE','APELLIDO P','APELLIDO M','IMPORTE'
            ];
            $keys = [
                'FechaPago','uidcajero','carrera','periodo','idServicio',
                'uid','nombre','primerApellido','segundoApellido','importe'
            ];

            if ($excel) {
                $export = new GenericExport($dataExcel, $headers, $keys);
                foreach ($cutRows as $r) $export->addCutRow($r);

                Excel::store($export, 'rptIngresosAnalitico.xlsx', 'public');

                return response()->json([
                    'status' => 200,
                    'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/rptIngresosAnalitico.xlsx'
                ]);
            }
        }

        /*
        =====================================================
        2️⃣ CONCENTRADO POR CAJERO
        =====================================================
        */
        if ($concentrado === 'SC') {

            $results = DB::table('edocta as cta')
                ->select(
                    'cta.uidcajero',
                    DB::raw("CONCAT(pers.nombre,' ',pers.primerApellido,' ',pers.segundoApellido) nombre"),
                    DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%EFECTIVO%' THEN cta.importe ELSE 0 END) efectivo"),
                    DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%TARJETA%' THEN cta.importe ELSE 0 END) tarjeta")
                )
                ->join('persona as pers', 'pers.uid', '=', 'cta.uidcajero')
                ->join('formaPago as fp', 'fp.idFormaPago', '=', 'cta.idformaPago')
                ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
                ->whereBetween('cta.FechaPago', [$inicio, $fin])
                ->where('cta.tipomovto', 'A')
                ->groupBy('cta.uidcajero','pers.nombre','pers.primerApellido','pers.segundoApellido')
                ->get();

            $dataExcel = [];
            $cutRows = [];
            $rowExcel = 2;

            foreach ($results as $r) {
                $dataExcel[] = ['uidcajero' => 'CAJERO: ' . $r->uidcajero];
                $cutRows[] = $rowExcel++;

                $dataExcel[] = (array)$r;
                $rowExcel++;

                $dataExcel[] = [
                    'uidcajero' => 'TOTAL CAJERO',
                    'efectivo'  => $r->efectivo,
                    'tarjeta'   => $r->tarjeta
                ];
                $cutRows[] = $rowExcel++;
            }

            $headers = ['CAJERO','NOMBRE','EFECTIVO','TRANSFERENCIA'];
            $keys = ['uidcajero','nombre','efectivo','tarjeta'];

            if ($excel) {
                $export = new GenericExport($dataExcel, $headers, $keys);
                foreach ($cutRows as $r) $export->addCutRow($r);

                Excel::store($export, 'rptIngresosCajero.xlsx', 'public');

                return response()->json([
                    'status' => 200,
                    'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/rptIngresosCajero.xlsx'
                ]);
            }
        }

        /*
        =====================================================
        3️⃣ CONCENTRADO POR CARRERA
        =====================================================
        */
        $results = DB::table('edocta as cta')
            ->select(
                'ca.idCarrera',
                'ca.descripcion',
                DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%EFECTIVO%' THEN cta.importe ELSE 0 END) efectivo"),
                DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%TARJETA%' THEN cta.importe ELSE 0 END) tarjeta")
            )
            ->join('alumno as al', function ($j) {
                $j->on('al.uid', '=', 'cta.uid')
                  ->on('al.secuencia', '=', 'cta.secuencia');
            })
            ->join('carrera as ca', function ($j) {
                $j->on('ca.idNivel', '=', 'al.idNivel')
                  ->on('ca.idCarrera', '=', 'al.idCarrera');
            })
            ->join('formaPago as fp', 'fp.idFormaPago', '=', 'cta.idformaPago')
            ->join('servicio as s', 's.idServicio', '=', 'cta.idServicio')
            ->whereBetween('cta.FechaPago', [$inicio, $fin])
            ->where('cta.tipomovto', 'A')
            ->groupBy('ca.idCarrera','ca.descripcion')
            ->get();

        $dataExcel = [];
        $cutRows = [];
        $rowExcel = 2;

        foreach ($results as $r) {
            $dataExcel[] = ['idCarrera' => 'CARRERA: ' . $r->descripcion];
            $cutRows[] = $rowExcel++;

            $dataExcel[] = (array)$r;
            $rowExcel++;

            $dataExcel[] = [
                'idCarrera' => 'TOTAL CARRERA',
                'efectivo'  => $r->efectivo,
                'tarjeta'   => $r->tarjeta
            ];
            $cutRows[] = $rowExcel++;
        }

        $headers = ['ID','CARRERA','EFECTIVO','TRANSFERENCIA'];
        $keys = ['idCarrera','descripcion','efectivo','tarjeta'];

        if ($excel) {
            $export = new GenericExport($dataExcel, $headers, $keys);
            foreach ($cutRows as $r) $export->addCutRow($r);

            Excel::store($export, 'rptIngresosCarrera.xlsx', 'public');

            return response()->json([
                'status' => 200,
                'message' => 'https://reportes.pruebas.siaweb.com.mx/storage/app/public/rptIngresosCarrera.xlsx'
            ]);
        }
    }
}
