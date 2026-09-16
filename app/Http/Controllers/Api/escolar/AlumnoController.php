<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;  
use Illuminate\Support\Facades\DB;
use App\Models\escolar\Alumno;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF; 
use App\Http\Controllers\Api\escolar\ReporteConcentradoExport;
use App\Http\Controllers\Api\escolar\ReporteDetalladoInscritosExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;  

class AlumnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function actualizaMonto(Request $request)
    {
        $data = $request->validate([
            'matricula' => 'required',
            'monto' => 'required|numeric|min:0',
        ]);

        $alumno = DB::table('alumno')
            ->where('matricula', $data['matricula'])
            ->first();

        if (!$alumno) {
            return $this->returnEstatus(
                'Alumno no encontrado',
                404,
                null
            );
        }

        DB::table('alumno')
            ->where('matricula', $data['matricula'])
            ->update(['monto' => $data['monto']]);

        return $this->returnData('alumno', [
            'matricula' => $data['matricula'],
            'monto' => $data['monto'],
        ], 200);
    }

    public function alumnosInscritosConcentrado($idNivel,$idPeriodo){
        
         $dataArray = $this->obtenerDatosConcentrado($idNivel,$idPeriodo);
         $totalesSemestre = $this->obtenerTotalesPorSemestre($idNivel,$idPeriodo);
 
         return $this->generateReportConcentrado($idNivel,$idPeriodo,
                                                    $dataArray,
                                                    ['CARRERA', 'TOTAL'],
                                                    [400, 50],
                                                    ['escuela', 'total'],
                                                    $totalesSemestre,
                                                    'CONCENTRADO DE INSCRITOS POR ESCUELA',
                                                    'L',
                                                    'letter',
                                                    'rptInscritosConcentradoEscuela' . mt_rand(100, 999) . '.pdf'
                                                    );
    }

    public function alumnosInscritosDetallado($idNivel,$idPeriodo){
       

        $resultado = DB::table('ciclos as cl')
                                    ->distinct()
                                    ->select(
                                        'p.uid',
                                     DB::raw("CONCAT_WS(' ', p.primerApellido, NULLIF(p.segundoApellido, ''), p.nombre) AS nombre"),
                                        'al.idCarrera',
                                        'c.descripcion',
                                        'cl.grupo',
                                        DB::raw("SUBSTRING(cl.grupo, CASE WHEN LENGTH(cl.grupo) = 4 THEN 3 ELSE 4 END, 1) AS semestre")
                                    )
                                    ->join('persona as p', 'cl.uid', '=', 'p.uid')
                                    ->join('alumno as al', function ($join) {
                                        $join->on('al.uid', '=', 'cl.uid')
                                            ->on('cl.secuencia', '=', 'al.secuencia');
                                    })
                                    ->join('carrera as c', function ($join) {
                                        $join->on('c.idCarrera', '=', 'al.idCarrera')
                                            ->on('c.idNivel', '=', 'al.idNivel');
                                    })
                                    ->where('cl.idNivel', $idNivel)
                                    ->where('cl.idPeriodo', $idPeriodo)
                                    ->orderBy('al.idCarrera')
                                    ->orderBy('semestre')
                                    ->orderBy('cl.grupo')
                                    ->orderBy('p.uid')  
                                    ->get();
        $dataArray = $resultado->map(function ($item) {
            return (array) $item;
        })->toArray();

        $carreras = $resultado
            ->map(function ($item) {
                return $item->idCarrera . ' - ' . $item->descripcion;
            })
            ->unique()
            ->values()
            ->toArray();

        $totalesSemestre = $resultado
            ->groupBy('semestre')
            ->map(function ($items, $semestre) {
                return [
                    'semestre' => $semestre,
                    'total' => $items->count()
                ];
            })
            ->sortBy('semestre')
            ->values()
            ->toArray();
 
         return $this->generateReportDtl($idNivel,$idPeriodo,
                                        $dataArray,
                                        ['UID', 'NOMBRE','GRUPO'],
                                        [60, 480,80],
                                        ['uid', 'nombre','grupo'],
                                        $carreras,
                                        $totalesSemestre,
                                        'DETALLADO DE INSCRITOS POR ESCUELA',
                                        'L',
                                        'letter',
                                        'rptInscritosDtlEscuela' . mt_rand(100, 999) . '.pdf'
                                        );
    }


    public function generateReportDtl($idNivel,$idPeriodo,$data, $headers,$columnWidths, $keys, $carreras, $totalesSemestre, $title, $orientation, $size, $nameReport){
    $imagePathEnc = public_path('images/encPag.png');
    $imagePathPie = public_path('images/piePag.png');
    $descripcionPeriodo = DB::table('periodo')
                        ->where('idNivel', $idNivel)
                        ->where('idPeriodo', $idPeriodo)
                        ->value('descripcion'); // devuelve solo el valor de la columna


    $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
    $pdf->setHeaders(null, $columnWidths, $title);
    $pdf->setImagePaths($imagePathEnc, $imagePathPie, $orientation);

    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(TRUE, 25);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 8);
    $html  = '<table width="100%" border="0" cellpadding="2">';
    $html .= '<tr>';
    $html .= '<td align="right" style="font-size:11pt;"><b>PERIODO '.$idPeriodo.' - '.$descripcionPeriodo.'</b></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td align="right" style="font-size:10pt;"><b>CARRERA(S): '.htmlspecialchars(implode(', ', $carreras)).'</b></td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html = $html.'<br><br><br><table border="0" cellpadding="2">';
    $html .= '<tr style="font-weight:bold; font-size:10px;">';
    
    foreach ($headers as $i => $h) 
         $html .= '<td width="'.$columnWidths[$i].'" align="left"><b>'.$h.'</b></td>';
            
            $html .= '</tr>';

    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($keys as $i => $k) {          
                $html .= '<td width="'.$columnWidths[$i].'" align="left">'.htmlspecialchars($row[$k]).'</td>';
        }
        $html .= '</tr>';
    }   
    $html .= '</table>';

    $html .= '<br><br><table border="0" cellpadding="2">';
    $html .= '<tr style="font-weight:bold; font-size:10px;">';
    $html .= '<td width="120">SEMESTRE</td>';
    $html .= '<td width="80" align="right">TOTAL</td>';
    $html .= '</tr>';

    $totalGeneral = 0;
    foreach ($totalesSemestre as $row) {
        $totalGeneral += $row['total'];
        $html .= '<tr>';
        $html .= '<td width="120">'.htmlspecialchars((string) $row['semestre']).'</td>';
        $html .= '<td width="80" align="right">'.$row['total'].'</td>';
        $html .= '</tr>';
    }

    $html .= '<tr style="font-weight:bold; font-size:10px;">';
    $html .= '<td width="120">TOTAL GENERAL</td>';
    $html .= '<td width="80" align="right">'.$totalGeneral.'</td>';
    $html .= '</tr>';
    $html .= '</table>';

    $pdf->writeHTML($html);

    $filePath = storage_path('app/public/' . $nameReport);
    $pdf->Output($filePath, 'F');

    return response()->json([
        'status' => 200,
        'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $nameReport
    ]);
}

public function generateReportConcentrado($idNivel,$idPeriodo,$data, $headers,$columnWidths, $keys, $totalesSemestre, $title, $orientation, $size, $nameReport)
{
    $imagePathEnc = public_path('images/encPag.png');
    $imagePathPie = public_path('images/piePag.png');

    $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
    $pdf->setHeaders(null, $columnWidths, $title);
    $pdf->setImagePaths($imagePathEnc, $imagePathPie, $orientation);
    $descripcionPeriodo = DB::table('periodo')
                        ->where('idNivel', $idNivel)
                        ->where('idPeriodo', $idPeriodo)
                        ->value('descripcion'); // devuelve solo el valor de la columna

    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(TRUE, 25);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 8);
    $html  = '<table width="100%" border="0" cellpadding="2">';
    $html .= '<tr>';
    $html .= '<td align="right" style="font-size:11pt;"><b>PERIODO '.$idPeriodo.' - '.$descripcionPeriodo.'</b></td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html = $html.'<br><br><br><table border="0" cellpadding="2">';
    $totalesGenerales = 0;
    $html .= '<tr style="font-weight:bold; font-size:10px;">';
    
    foreach ($headers as $i => $h) {
                $align = ($i == 1) ? 'right' : 'left';
                $html .= '<td width="'.$columnWidths[$i].'" align="'.$align.'"><b>'.$h.'</b></td>';
            }
    $html .= '</tr>';

    foreach ($data as $row) {
        // Fila por escuela
        $html .= '<tr>';
        foreach ($keys as $i => $k) {
            if ($k === 'escuela') {
                $html .= '<td width="'.$columnWidths[$i].'" align="left">'.htmlspecialchars($row[$k]).'</td>';
            } else {
                $value = floatval($row[$k]);
                $html .= '<td width="'.$columnWidths[$i].'" align="right"> '.$value.'</td>';
                // Totales globales
                $totalesGenerales = ($totalesGenerales ?? 0) + $value;
            }
        }
        $html .= '</tr>';
    }   

    // TOTAL GENERAL
    $html .= '<tr><td colspan="'.count($keys).'"><br><br></td></tr>';
    $html .= '<tr style="font-weight:bold; font-size:10px;">';
    $html .= '<td>TOTAL ALUMNOS</td>';
    $html .= '<td align="right"> '.$totalesGenerales.'</td>';    
    $html .= '</tr>';
    $html .= '</table>';

    $html .= '<br><br><table border="0" cellpadding="2">';
    $html .= '<tr style="font-weight:bold; font-size:10px;">';
    $html .= '<td width="120">SEMESTRE</td>';
    $html .= '<td width="80" align="right">TOTAL</td>';
    $html .= '</tr>';

    $totalSemestres = 0;
    foreach ($totalesSemestre as $row) {
        $totalSemestres += $row['total'];
        $html .= '<tr>';
        $html .= '<td width="120">'.htmlspecialchars((string) $row['semestre']).'</td>';
        $html .= '<td width="80" align="right">'.$row['total'].'</td>';
        $html .= '</tr>';
    }

    $html .= '<tr style="font-weight:bold; font-size:10px;">';
    $html .= '<td width="120">TOTAL GENERAL</td>';
    $html .= '<td width="80" align="right">'.$totalSemestres.'</td>';
    $html .= '</tr>';
    $html .= '</table>';

    $pdf->writeHTML($html);

    $filePath = storage_path('app/public/' . $nameReport);
    $pdf->Output($filePath, 'F');

    return response()->json([
        'status' => 200,
        'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $nameReport
    ]);
}

public function alumnosInscritosDetalladoExc($idNivel,$idPeriodo) {
        $name = 'detalleInscritos'.rand(1, 999).'.xlsx';
        $path = storage_path('app/public/'.$name);

        $resultado = DB::table('ciclos as cl')
                                    ->distinct()
                                    ->select(
                                        'p.uid',
                                     DB::raw("CONCAT_WS(' ', p.primerApellido, NULLIF(p.segundoApellido, ''), p.nombre) AS nombre"),
                                        'al.idCarrera',
                                        'c.descripcion',
                                        'cl.grupo',
                                        DB::raw("SUBSTRING(cl.grupo, CASE WHEN LENGTH(cl.grupo) = 4 THEN 3 ELSE 4 END, 1) AS semestre")
                                    )
                                    ->join('persona as p', 'cl.uid', '=', 'p.uid')
                                    ->join('alumno as al', function ($join) {
                                        $join->on('al.uid', '=', 'cl.uid')
                                            ->on('cl.secuencia', '=', 'al.secuencia');
                                    })
                                    ->join('carrera as c', function ($join) {
                                        $join->on('c.idCarrera', '=', 'al.idCarrera')
                                            ->on('c.idNivel', '=', 'al.idNivel');
                                    })
                                    ->where('cl.idNivel', $idNivel)
                                    ->where('cl.idPeriodo', $idPeriodo)
                                    ->orderBy('al.idCarrera')
                                    ->orderBy('semestre')
                                    ->orderBy('cl.grupo')
                                    ->orderBy('p.uid')
                                    ->get();

        $dataArray = $resultado->map(function ($item) {
            return (array) $item;
        })->toArray();

        $descripcionPeriodo = DB::table('periodo')
                        ->where('idNivel', $idNivel)
                        ->where('idPeriodo', $idPeriodo)
                        ->value('descripcion');

        $carreras = $resultado
            ->map(function ($item) {
                return $item->idCarrera . ' - ' . $item->descripcion;
            })
            ->unique()
            ->values()
            ->toArray();

        $totalesSemestre = $resultado
            ->groupBy('semestre')
            ->map(function ($items, $semestre) {
                return [
                    'semestre' => $semestre,
                    'total' => $items->count()
                ];
            })
            ->sortBy('semestre')
            ->values()
            ->toArray();

        $export = new ReporteDetalladoInscritosExport(
            $dataArray,
            $idPeriodo.' - '.$descripcionPeriodo,
            $carreras,
            $totalesSemestre
        );

        Excel::store($export, $name, 'public');

        if (file_exists($path))  {
            return response()->json([
                'status' => 200,
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/'.$name
            ]);
        }

        return response()->json([
            'status' => 500,
            'message' => 'Error al generar el reporte '
        ]);
    }
public function obtenerDatosConcentrado($idNivel,$idPeriodo){

    $resultado = DB::table('ciclos as cl')
                                    ->select(
                                        'c.idCarrera',
                                        'c.descripcion AS escuela',
                                     DB::raw('COUNT(DISTINCT p.uid) as total')
                                    )
                                    ->join('persona as p', 'cl.uid', '=', 'p.uid')
                                    ->join('alumno as al', function ($join) {
                                        $join->on('al.uid', '=', 'cl.uid')
                                            ->on('cl.secuencia', '=', 'al.secuencia');
                                    })
                                    ->join('carrera as c', function ($join) {
                                        $join->on('c.idCarrera', '=', 'al.idCarrera')
                                            ->on('c.idNivel', '=', 'al.idNivel');
                                    })
                                    ->where('cl.idNivel', $idNivel)
                                    ->where('cl.idPeriodo', $idPeriodo)
                                    ->groupBy('c.idCarrera', 'c.descripcion')
                                    ->get();

        $dataArray = $resultado->map(function ($item) {
            return (array) $item;
        })->toArray();
        return $dataArray;
}

public function obtenerTotalesPorSemestre($idNivel,$idPeriodo){

    $resultado = DB::table('ciclos as cl')
                                    ->select(
                                        DB::raw("SUBSTRING(cl.grupo, CASE WHEN LENGTH(cl.grupo) = 4 THEN 3 ELSE 4 END, 1) AS semestre"),
                                        DB::raw('COUNT(DISTINCT cl.uid, cl.secuencia) as total')
                                    )
                                    ->where('cl.idNivel', $idNivel)
                                    ->where('cl.idPeriodo', $idPeriodo)
                                    ->groupBy(DB::raw("SUBSTRING(cl.grupo, CASE WHEN LENGTH(cl.grupo) = 4 THEN 3 ELSE 4 END, 1)"))
                                    ->orderBy('semestre')
                                    ->get();

        return $resultado->map(function ($item) {
            return (array) $item;
        })->toArray();
}

public function exportExcelCocentrado($idNivel,$idPeriodo)
{

    $descripcionPeriodo = DB::table('periodo')
                        ->where('idNivel', $idNivel)
                        ->where('idPeriodo', $idPeriodo)
                        ->value('descripcion'); // devuelve solo el valor de la columna

    $dataArray = $this->obtenerDatosConcentrado($idNivel,$idPeriodo);
    $totalesSemestre = $this->obtenerTotalesPorSemestre($idNivel,$idPeriodo);

    $headers = ['escuela', 'total'];
    $fileName = 'rptInscritosConcEscuela_'.mt_rand(100,999).'.xlsx';
    $path = storage_path('app/public/'.$fileName);
 
    Excel::store(new ReporteConcentradoExport($dataArray, $headers, 'PERIODO '.$idPeriodo.' - '.$descripcionPeriodo, $totalesSemestre), $fileName, 'public');

   
    if (file_exists($path)) {
        return response()->json([
            'status' => 200,
            'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $fileName
            
        ]);
    } else {
        return response()->json([
            'status' => 500,
            'message' => 'Error al generar el reporte'
        ]);
    }
}

public function getAvance($uid,$secuencia){
        $avance = DB::select('SELECT PorcentajeAvance(?, ?) AS avance', [$uid, $secuencia]);

        if (!$avance) {
            $data = [  
                'message' => 'Alumno no encontrado',   
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'avance' => $avance,
            'status' => 200
        ];
        return response()->json($data, 200);
    }

   public function getAlumno($uid){

    $alumnos = DB::table('alumno as a')
        ->join('nivel as n', 'n.idNivel', '=', 'a.idNivel')

        ->join('carrera as c', function ($join) {
            $join->on('c.idCarrera', '=', 'a.idCarrera')
                 ->on('c.idNivel', '=', 'a.idNivel');
        })
        ->join('persona as p', function ($join) use ($uid) {
                $like = "%{$uid}%";

                $join->on('p.uid', '=', 'a.uid')
                    ->whereRaw("
                        (
                            CONCAT_WS(' ', p.primerApellido, NULLIF(p.segundoApellido, ''), p.nombre) LIKE ?                            
                            OR p.uid LIKE ?
                            OR a.matricula LIKE ?
                        )
                    ", [$like, $like, $like]);
            })
        ->leftJoin('ciudad as ci', function ($join) {
            $join->on('ci.idEstado', '=', 'p.idEstado')
                 ->on('ci.idPais', '=', 'p.idPais')
                 ->on('ci.idCiudad', '=', 'p.idCiudad');
        })

        ->leftJoin('estado as e', function ($join) {
            $join->on('e.idEstado', '=', 'p.idEstado')
                 ->on('e.idPais', '=', 'p.idPais');
        })

        ->leftJoin('pais as pa', 'pa.idPais', '=', 'p.idPais')

        ->leftJoin('edoCivil as ec', 'ec.idEdoCivil', '=', 'p.idEdoCivil')

        // Periodo activo del nivel del alumno
        ->join('periodo as perActivo', function ($join) {
            $join->on('perActivo.idNivel', '=', 'a.idNivel')
                 ->where('perActivo.activo', '=', 1);
        })

      ->leftJoin('ciclos as sc', function ($join) {
                $join->on('sc.uid', '=', 'a.uid')
                    ->on('sc.secuencia', '=', 'a.secuencia')
                    ->whereRaw("
                        sc.idPeriodo IN (perActivo.idPeriodo, perActivo.idPeriodo + 1)
                    ");
            })  // Periodo real del ciclo encontrado
        ->leftJoin('periodo as per', function ($join) {
            $join->on('per.idPeriodo', '=', 'sc.idPeriodo')
                 ->on('per.idNivel', '=', 'a.idNivel');
        })

      ->select([
                    'a.uid',
                    'a.activo',
                    'a.idNivel',
                    'a.secuencia',
                    'a.idCarrera',
                    'a.matricula',
                    'a.monto',
                    'n.descripcion as nivel',
                    'c.descripcion as nombreCarrera',

                    'p.curp',
                    DB::raw("CONCAT_WS(' ', p.primerApellido, NULLIF(p.segundoApellido, ''), p.nombre) AS nombre"),

                    'p.primerApellido',
                    'p.segundoApellido',
                    'p.sexo',
                    'p.rfc',
                    'p.fechaNacimiento',

                    'ci.descripcion as ciudad',
                    'e.descripcion as estado',
                    'pa.descripcion as pais',
                    'ec.descripcion as edoCivil',

                    'sc.idPeriodo',
                    'sc.grupo',
                    'per.descripcion as periodo',
                ])
                ->get();
    if ($alumnos->isEmpty()) {
        $data = [
            'message' => 'Alumno no encontrado',
            'status' => 404
        ];

        return response()->json($data, 404);
    }

    $data = [
        'alumnos' => $alumnos,
        'status' => 200
    ];

    return response()->json($data, 200);
}
}
