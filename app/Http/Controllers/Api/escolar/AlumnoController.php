<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;  
use Illuminate\Support\Facades\DB;
use App\Models\escolar\Alumno;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF; 
use App\Http\Controllers\Api\escolar\ReporteConcentradoExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Api\serviciosGenerales\GenericTableExportEsp;  

class AlumnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function alumnosInscritosConcentrado($idNivel,$idPeriodo){
        
         $dataArray = $this->obtenerDatosConcentrado($idNivel,$idPeriodo);
 
         return $this->generateReportConcentrado($idNivel,$idPeriodo,
                                                    $dataArray,
                                                    ['CARRERA', 'TOTAL'],
                                                    [400, 50],
                                                    ['escuela', 'total'],            
                                                    'CONCENTRADO DE INSCRITOS POR ESCUELA',
                                                    'L',
                                                    'letter',
                                                    'rptInscritosConcentradoEscuela' . mt_rand(100, 999) . '.pdf'
                                                    );
    }

    public function alumnosInscritosDetallado($idNivel,$idPeriodo){
       

        $resultado = DB::table('grupos as gpo')
                                    ->distinct()
                                    ->select(
                                        'p.uid',
                                         DB::raw('CONCAT(p.primerApellido, " ", p.segundoApellido, " ", p.nombre) AS nombre'),
                                        'al.idCarrera',
                                        'c.descripcion'
                                    )
                                    ->join('ciclos as cl', function ($join) {
                                        $join->on('cl.grupo', '=', 'gpo.grupo')
                                            ->on('cl.idNivel', '=', 'gpo.idNivel')
                                            ->on('cl.idPeriodo', '=', 'gpo.idPeriodo');
                                    })
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
                                    ->orderBy('p.uid')  
                                    ->get();
        $dataArray = $resultado->map(function ($item) {
            return (array) $item;
        })->toArray();
 
         return $this->generateReportDtl($idNivel,$idPeriodo,
                                        $dataArray,
                                        ['UID', 'NOMBRE','CVE CARRERA','NOMBRE CARRERA'],
                                        [50, 400,100,200],
                                        ['uid', 'nombre','idCarrera','descripcion'],            
                                        'DETALLADO DE INSCRITOS POR ESCUELA',
                                        'L',
                                        'letter',
                                        'rptInscritosDtlEscuela' . mt_rand(100, 999) . '.pdf'
                                        );
    }


    public function generateReportDtl($idNivel,$idPeriodo,$data, $headers,$columnWidths, $keys, $title, $orientation, $size, $nameReport){
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
    $html .= '</table>';
    $html = $html.'<br><br><br><table border="0" cellpadding="2">';
    $totalesGenerales = 0;
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

    $pdf->writeHTML($html);

    $filePath = storage_path('app/public/' . $nameReport);
    $pdf->Output($filePath, 'F');

    return response()->json([
        'status' => 200,
        'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $nameReport
    ]);
}

public function generateReportConcentrado($idNivel,$idPeriodo,$data, $headers,$columnWidths, $keys, $title, $orientation, $size, $nameReport)
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

    $pdf->writeHTML($html);

    $filePath = storage_path('app/public/' . $nameReport);
    $pdf->Output($filePath, 'F');

    return response()->json([
        'status' => 200,
        'message' => 'https://reportes.siaweb.com.mx/storage/app/public/' . $nameReport
    ]);
}

public function alumnosInscritosDetalladoExc($idNivel,$idPeriodo) {  
        // Ruta del archivo a almacenar en el disco público
        $path = storage_path('app/public/detalleInscritos.xlsx');
        $selectColumns = [ 'persona.uid',
                            DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre'),
                            'alumno.idCarrera',
                            'carrera.descripcion'
                 ]; // Seleccionar columnas específicas
        $namesColumns = ['UID','NOMBRE','CVE CARRERA','NOMBRE CARRERA']; // Seleccionar columnas específicas
        
        $joins = [['table' => 'ciclos', 
                   'type' => 'inner',
                   'conditions' => [
                        ['first' => 'ciclos.grupo', 'second' => 'grupos.grupo'],
                        ['first' => 'ciclos.idNivel', 'second' => 'grupos.idNivel'],
                        ['first' => 'ciclos.idPeriodo', 'second' => 'grupos.idPeriodo']
                    ]
                  ],
                  ['table' => 'alumno', 
                   'type' => 'inner',
                   'conditions' => [
                        ['first' => 'alumno.uid', 'second' => 'ciclos.uid'],
                        ['first' => 'alumno.secuencia', 'second' => 'ciclos.secuencia']
                    ]
                   ],
                    ['table' => 'persona', 
                    'type' => 'inner',
                    'conditions' => [
                        ['first' => 'ciclos.uid','second' => 'persona.uid']]                     
                    ],
                    ['table' => 'carrera', 
                     'type' => 'left' ,
                     'conditions' => [
                        ['first' => 'carrera.idCarrera', 'second' => 'alumno.idCarrera'],
                        ['first' => 'carrera.idNivel', 'second' => 'alumno.idNivel']
                     ]                    
                    ]
            ]; 

        $filters = [ 'alumno.idNivel' => $idNivel,
                    'ciclos.idPeriodo' => $idPeriodo];

        $export = new GenericTableExportEsp('grupos', 'uid', $filters, ['alumno.idCarrera','persona.uid'], ['asc','asc'], $selectColumns, $joins,$namesColumns);

        // Guardar el archivo en el disco público
        Excel::store($export, 'detalleInscritos.xlsx', 'public');
       
        // Verifica si el archivo existe usando Storage de Laravel
        if (file_exists($path))  {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/detalleInscritos.xlsx' // URL pública para descargar el archivo
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte '
            ]);
        }  
    }

public function obtenerDatosConcentrado($idNivel,$idPeriodo){

    $resultado = DB::table('grupos as gpo')
                                    ->select(
                                        'c.idCarrera',
                                        'c.descripcion AS escuela',
                                        DB::raw('COUNT(DISTINCT p.uid, p.primerApellido, p.segundoApellido, p.nombre, al.idCarrera, c.descripcion) as total')
                                    )
                                    ->join('ciclos as cl', function ($join) {
                                        $join->on('cl.grupo', '=', 'gpo.grupo')
                                            ->on('cl.idNivel', '=', 'gpo.idNivel')
                                            ->on('cl.idPeriodo', '=', 'gpo.idPeriodo');
                                    })
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

public function exportExcelCocentrado($idNivel,$idPeriodo)
{

    $descripcionPeriodo = DB::table('periodo')
                        ->where('idNivel', $idNivel)
                        ->where('idPeriodo', $idPeriodo)
                        ->value('descripcion'); // devuelve solo el valor de la columna

    $dataArray = $this->obtenerDatosConcentrado($idNivel,$idPeriodo);

    $headers = ['escuela', 'total'];
    $fileName = 'rptInscritosConcEscuela_'.mt_rand(100,999).'.xlsx';
    $path = storage_path('app/public/'.$fileName);
 
    Excel::store(new ReporteConcentradoExport($dataArray, $headers, 'PERIODO '.$idPeriodo.' - '.$descripcionPeriodo), $fileName, 'public');

   
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

        $subCiclos = DB::table('ciclos')
                    ->select('uid', 'secuencia', DB::raw('MAX(idPeriodo) as idPeriodo'))
                    ->groupBy('uid', 'secuencia');

        $alumnos = DB::table('alumno')
                    ->join('nivel', 'nivel.idNivel', '=', 'alumno.idNivel')
                    ->join('carrera', 'carrera.idCarrera', '=', 'alumno.idCarrera')
                    ->join('persona', 'persona.uid', '=', 'alumno.uid')
                    ->leftJoin('ciudad', function($join) {
                        $join->on('ciudad.idEstado', '=', 'persona.idEstado')
                             ->on('ciudad.idPais', '=', 'persona.idPais')
                             ->on('ciudad.idCiudad', '=', 'persona.idCiudad');
                        })
                    ->leftJoin('estado', function($join) {
                    $join->on('estado.idEstado', '=', 'persona.idEstado')->on('estado.idPais', '=', 'persona.idPais');
            })
            ->leftJoin('pais', 'pais.idPais', '=', 'persona.idPais')
            ->leftJoin('edoCivil', 'edoCivil.idEdoCivil', '=', 'persona.idEdoCivil')
        ->leftJoinSub($subCiclos, 'c', function ($join) {
                $join->on('c.uid', '=', 'alumno.uid')
                    ->on('c.secuencia', '=', 'alumno.secuencia');
            })
            ->leftJoin('periodo as p', function ($join) {
                $join->on('p.idPeriodo', '=', 'c.idPeriodo')
                    ->on('p.idNivel', '=', 'alumno.idNivel');
            })
            ->where(function($query) use ($uid) {
                $query->where(
                    DB::raw("CONCAT(persona.nombre, ' ', persona.primerApellido, ' ', persona.segundoApellido)"), 'LIKE', '%'.$uid.'%')
                    ->orWhere(
                        DB::raw("CONCAT(persona.primerApellido, ' ', persona.segundoApellido, ' ', persona.nombre)"), 'LIKE', '%'.$uid.'%')
                            ->orWhere('persona.nombre', 'LIKE', '%'.$uid.'%')
                            ->orWhere('persona.primerApellido', 'LIKE', '%'.$uid.'%')
                            ->orWhere('persona.segundoApellido', 'LIKE', '%'.$uid.'%')
                            ->orWhere('persona.uid', 'LIKE', '%'.$uid.'%');
                    })
                    ->select(   'alumno.uid'
                    ,
                        'alumno.idNivel',
                        'alumno.secuencia',
                        'alumno.idCarrera',
                        'alumno.matricula',
                        'nivel.descripcion as nivel',
                        'carrera.descripcion as nombreCarrera',
                        'persona.curp',
                        'persona.nombre',
                        'persona.primerapellido',
                        'persona.segundoapellido',
                        'persona.sexo',
                        'persona.rfc',
                        'persona.fechaNacimiento',
                        'ciudad.descripcion as ciudad',
                        'estado.descripcion as estado',
                        'pais.descripcion as pais',
                        'edoCivil.descripcion as edocivil',
                        'c.idPeriodo',
                        'p.descripcion as periodo'
            )
            ->get();

            if (!$alumnos) {
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
