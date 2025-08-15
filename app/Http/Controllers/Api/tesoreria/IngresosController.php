<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\EstadoCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;  

class IngresosController extends Controller  
{
    
    public function index($concentrado, $idFchInicio, $idFechaFin, $idCajero = null, $idCarrera = null)
{
    if ($concentrado == 'N') {
        $data = DB::table('edocta as cta')
            ->select(
                'cta.uid',
                'pers.nombre',
                'pers.primerApellido',
                'pers.segundoApellido',
                'cta.importe',
                'cta.idformaPago',
                'cta.FechaPago',
                'cta.idServicio',
                'fp.descripcion as formadep',
                'cta.idPeriodo',
                'p.descripcion as periodo',
                'cta.uidcajero',
                'ca.descripcion as carrera'
            )
            ->join('persona as pers', 'pers.uid', '=', 'cta.uid')
            ->join('alumno as al', function ($join) {
                $join->on('al.uid', '=', 'cta.uid')
                     ->on('al.secuencia', '=', 'cta.secuencia');
            })
            ->join('periodo as p', function ($join) {
                $join->on('p.idPeriodo', '=', 'cta.idPeriodo')
                     ->on('p.idNivel', '=', 'al.idNivel');
            })
            ->join('formaPago as fp', 'fp.idFormaPago', '=', 'cta.idformaPago')
            ->join('carrera as ca', function ($join) {
                $join->on('ca.idNivel', '=', 'al.idNivel')
                     ->on('ca.idCarrera', '=', 'al.idCarrera');
            })
            ->where('cta.FechaPago', '>=', DB::raw("STR_TO_DATE('" . $idFchInicio . "', '%Y-%m-%d')"))
            ->where('cta.FechaPago', '<=', DB::raw("STR_TO_DATE('" . $idFechaFin . "', '%Y-%m-%d')"))
            ->where('cta.tipomovto', '=', 'A');

        if (isset($idCajero)) 
            $results->where('cta.uidcajero', '=', $idCajero);   
        
        if (isset($idCarrera)) 
            $results->where('ca.idCarrera', '=', $idCarrera);
        
        $results = $data->get();     

        $count = $results->count();
        
        if($count==0)
             return response()->json([
                'status' => 500,
                'message' => 'No hay registros para mostrar'
            ]);

        $headers = ['FECHA PAGO', 'CAJERO', 'CARRERA', 'PERIODO', 'SERVICIO', 'UID', 'NOMBRE', 'APELLIDO PATERNO', 'APELLIDO MATERNO', 'IMPORTE'];
        $columnWidths = [50, 50, 150, 80, 50, 50, 80, 80, 80, 80];
        $keys = ['FechaPago', 'uidcajero', 'carrera', 'periodo', 'idServicio', 'uid', 'nombre', 'primerApellido', 'segundoApellido', 'importe', 'formadep'];

        $resultsArray = $results->map(function ($item) {
            return (array) $item; // Convertir cada stdClass a un arreglo
        })->toArray();

        return $this->generateReport(
            $resultsArray,
            $columnWidths,
            $keys,
            'REPORTE DE INGRESOS ANALÍTICO',
            $headers,
            'L',
            'letter',
            'rptIngresos' . mt_rand(100, 999) . '.pdf'
        );
    } else if($concentrado == 'SC'){
            $data = DB::table('edocta as cta')
                            ->select(
                                'cta.uidcajero',
                                DB::raw("CONCAT(pers.nombre, ' ', pers.primerApellido, ' ', pers.segundoApellido) AS nombre"),
                                DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%EFECTIVO%' THEN cta.importe ELSE 0 END) AS efectivo"),
                                DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%TARJETA%' THEN cta.importe ELSE 0 END) AS tarjeta")
                            )
                            ->join('persona as pers', 'pers.uid', '=', 'cta.uidcajero')
                            ->join('formaPago as fp', 'fp.idFormaPago', '=', 'cta.idformaPago')
                            ->join('alumno as al', function ($join) {
                                            $join->on('al.uid', '=', 'cta.uid')
                                                 ->on('al.secuencia', '=', 'cta.secuencia');
                            })            
                            ->join('carrera as ca', function ($join) {
                                            $join->on('ca.idNivel', '=', 'al.idNivel')
                                                  ->on('ca.idCarrera', '=', 'al.idCarrera');
                            })
                            ->where('cta.tipomovto', '=', 'A')
                            ->where('cta.FechaPago', '>=', DB::raw("STR_TO_DATE('" . $idFchInicio . "', '%Y-%m-%d')"))
                            ->where('cta.FechaPago', '<=', DB::raw("STR_TO_DATE('" . $idFechaFin . "', '%Y-%m-%d')"))
                            ->groupBy('cta.uidcajero', 'pers.nombre', 'pers.primerApellido', 'pers.segundoApellido');

            if (isset($idCajero)) 
                $results->where('cta.uidcajero', '=', $idCajero);   
        
            if (isset($idCarrera)) 
                $results->where('ca.idCarrera', '=', $idCarrera);
        
            $results = $data->get();

            $count = $results->count();
            
            if($count==0)
                return response()->json([
                    'status' => 500,
                    'message' => 'No hay registros para mostrar'
                ]);       
            
        $headers = ['CAJERO', 'NOMBRE', 'EFECTIVO', 'TRANSFERENCIA'];
        $columnWidths = [50, 150, 100, 100];
        $keys = ['uidcajero', 'nombre', 'efectivo', 'tarjeta'];

        $resultsArray = $results->map(function ($item) {
            return (array) $item; // Convertir cada stdClass a un arreglo
        })->toArray();

        return $this->generateReport(
            $resultsArray,
            $columnWidths,
            $keys,
            'REPORTE DE INGRESOS CONCENTRADO POR CAJERO',
            $headers,
            'P',
            'letter',
            'rptIngresos' . mt_rand(100, 999) . '.pdf'
        );
    }
    else{
                    $data = DB::table('edocta as cta')
                            ->select('ca.idCarrera', 'ca.descripcion',
                                DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%EFECTIVO%' THEN cta.importe ELSE 0 END) AS efectivo"),
                                DB::raw("SUM(CASE WHEN fp.descripcion LIKE '%TARJETA%' THEN cta.importe ELSE 0 END) AS tarjeta")
                            )
                             ->join('alumno as al', function ($join) {
                                            $join->on('al.uid', '=', 'cta.uid')
                                                 ->on('al.secuencia', '=', 'cta.secuencia');
                            })            
                            ->join('carrera as ca', function ($join) {
                                            $join->on('ca.idNivel', '=', 'al.idNivel')
                                                 ->on('ca.idCarrera', '=', 'al.idCarrera');
                            })
                            ->join('formaPago as fp', 'fp.idFormaPago', '=', 'cta.idformaPago')
                            ->where('cta.tipomovto', '=', 'A')
                            ->where('cta.FechaPago', '>=', DB::raw("STR_TO_DATE('" . $idFchInicio . "', '%Y-%m-%d')"))
                            ->where('cta.FechaPago', '<=', DB::raw("STR_TO_DATE('" . $idFechaFin . "', '%Y-%m-%d')"))
                            ->groupBy('ca.idCarrera', 'ca.descripcion');

            if (isset($idCajero)) 
                $results->where('cta.uidcajero', '=', $idCajero);   
        
            if (isset($idCarrera)) 
                $results->where('ca.idCarrera', '=', $idCarrera);
        
            $results = $data->get();

            $count = $results->count();
            
            if($count==0)
                return response()->json([
                    'status' => 500,
                    'message' => 'No hay registros para mostrar'
                ]);       
            
        $headers = ['ID', 'CARRERA', 'EFECTIVO', 'TRANSFERENCIA'];
        $columnWidths = [50, 200, 100, 100];
        $keys = ['idCarrera', 'descripcion', 'efectivo', 'tarjeta'];

        $resultsArray = $results->map(function ($item) {
            return (array) $item; // Convertir cada stdClass a un arreglo
        })->toArray();   

        return $this->generateReport(
                        $resultsArray,
                        $columnWidths,
                        $keys,
                        'REPORTE DE INGRESOS CONCENTRADO POR CARRERA',
                        $headers,
                        'P',
                        'letter',
                        'rptIngresos' . mt_rand(100, 999) . '.pdf'
                    );
        }
  }

    
    public function generateReport($data, $columnWidths, $keys, $title, $headers, $orientation, $size, $nameReport)
    {
        // Rutas de las imágenes para el encabezado y pie
        $imagePathEnc = public_path('images/encPag.png');
        $imagePathPie = public_path('images/piePag.png');
        // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)
        $pdf = new CustomTCPDF($orientation, PDF_UNIT, $size, true, 'UTF-8', false);
        
        // Configurar los encabezados, las rutas de las imágenes y otros parámetros
        $pdf->setHeaders(null, $columnWidths, $title);
        $pdf->setImagePaths($imagePathEnc, $imagePathPie,$orientation);
        
        // Configurar las fuentes
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SIAWEB');
        
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();

        // Establecer fuente para el cuerpo del documento
        $pdf->SetFont('helvetica', '', 8);
         // Generar la tabla HTML para los datos
        $html2 = '<br><br><br><table border="0" cellpadding="1">';
        $html2 .= '<tr>';  
        foreach ($headers as $index => $header)
            $html2 .= '<td style="font-size: 9px;" width="' . $columnWidths[$index] . '"><b>' . htmlspecialchars($header) . '</b></td>';
        $html2 .= '</tr>';
        $html2 .= '<tr><td colspan="7"></td></tr>';
        $total = 0;
        $totalEfectivo = 0;
        $totalTarjeta = 0;
        $tipoPago='';  

        foreach ($data as $index2 => $row) {  
            
             if($tipoPago!="" && $tipoPago!=$row['formadep']){   //corte por forma de pago
                        $html2 .= '<tr><td colspan="10"><hr style="border: 1px dotted black; background-size: 20px 10px;"></td></tr>';
                        $html2 .= '<p style="font-weight: bold; font-size: 12px;"> TOTAL $ '.$tipoPago.' '.number_format($total, 2, '.', ',').'</p><br><br>';
                        $total=0;
                    }           
              $html2 .= '<tr>';   
              foreach ($keys as $index => $key) { 
                if($key!='formadep'){
                    if($key=='importe'){
                        $total += $row[$key] ?? 0;
                    }
                    if($key=='efectivo'){
                        $totalEfectivo += $row[$key] ?? 0;
                    }
                    if($key=='tarjeta'){
                        $totalTarjeta += $row[$key] ?? 0;
                    }
                $value = isset($row[$key]) ? $row[$key] : '';    
                $html2 .= '<td width="' . $columnWidths[$index] . '">' . ($value !== null ? htmlspecialchars((string)$value) : '') . '</td>';
             }}
            $html2 .= '</tr>';   

            if(isset($row['formadep']))        
                $tipoPago=$row['formadep'];           
        }

        $html2 .= '<tr><td colspan="10"><hr style="border: 1px dotted black; background-size: 20px 10px;"></td></tr>';
        $html2 .= '</table>';
          if($total>0)
           $html2 .= '<br><p style="font-weight: bold; font-size: 12px;"> TOTAL $ '.$tipoPago.' '.number_format($total, 2, '.', ',').'</p><br><br>';
       
        if($totalEfectivo>0)
           $html2 .= '<br><p style="font-weight: bold; font-size: 12px;"> TOTAL EFECTIVO $ '.number_format($totalEfectivo, 2, '.', ',').'</p>';
      
         if($totalTarjeta>0)
           $html2 .= '<p style="font-weight: bold; font-size: 12px;"> TOTAL TARJETA $ '.number_format($totalTarjeta, 2, '.', ',').'</p>';
   
        // Escribir la tabla en el PDF
        $pdf->writeHTML($html2, true, false, true, false, '');

        if($nameReport==null)
            $filePath = storage_path('app/public/reporte.pdf');  // Ruta donde se guardará el archivo
        else $filePath = storage_path('app/public/'.$nameReport);  // Ruta donde se guardará el archivo
       
        $pdf->Output($filePath, 'F');  // 'F' para guardar el archivo en el servidor
    
        // Ahora puedes verificar si el archivo se ha guardado correctamente en la ruta especificada.
        if (file_exists($filePath)) {
            return response()->json([
                'status' => 200,  
                'message' => 'https://reportes.siaweb.com.mx/storage/app/public/'.$nameReport // Puedes devolver la ruta para fines de depuración
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'Error al generar el reporte'
            ]);
        }    
    }
    
}
