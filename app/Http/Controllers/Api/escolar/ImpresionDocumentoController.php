<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;  
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\escolar\KardexController;


class ImpresionDocumentoController extends Controller{

    protected $kardexService;

    public function __construct(KardexController $kardexService){
        $this->kardexService = $kardexService;
    }

    public function generaReporte($idServicio,$matricula,$folio){
        //folio es vacio o 0 entonces se manda la impresion con marca de agua
        
        $alumno = DB::table('alumno')
                        ->where('matricula', $matricula)           
                        ->select('alumno.idNivel',
                                 'alumno.uid',
                                 'alumno.secuencia',
                                 'alumno.idCarrera')
                        ->first();

        $servicios = DB::table('configuracionImpresion')
                        ->where('idServicio', $idServicio)           
                        ->select('metodo')
                        ->first();
        
        if (!$servicios)
            return $this->returnEstatus('El servicio no se encuentra configurado para impresión',404,null);
        

        $aquaMark = empty($folio);
       
        if($folio>0){
            //actualizamos el pago del documento
            $periodo = DB::table('periodo')
                            ->select('idPeriodo')
                            ->where('activo', 1)
                            ->where('idNivel', $alumno->idNivel)
                            ->first();

             // validamos si existe un documento pagado a ese servicio
            $registros = DB::table('edocta')
                            ->where('uid', $alumno->uid)
                            ->where('idServicio', $idServicio)
                            ->where('folio', $folio)
                            ->where('idPeriodo', $periodo->idPeriodo)
                            ->where('tipomovto', 'A')
                            ->where('doctoImpreso', 1)
                            ->get();

            if (!$registros->isEmpty())
                  return $this->returnEstatus('El documento relacionado a ese folio ya fue impreso',404,null);
        
            // validamos si existe un documento pagado a ese servicio
            $registros = DB::table('edocta')
                            ->where('uid', $alumno->uid)
                            ->where('idServicio', $idServicio)
                            ->where('folio', $folio)
                            ->where('idPeriodo', $periodo->idPeriodo)
                            ->where('tipomovto', 'A')
                            ->whereNull('doctoImpreso')
                            ->get();

            if ($registros->isEmpty())
                  return $this->returnEstatus('No existe un pago para el servicio seleccionado con el folio ingresado',404,null);
        
            //Actualizamos el servicio pagado
            DB::table('edocta')
                            ->where('uid', $alumno->uid)
                            ->where('idServicio', $idServicio)
                            ->where('folio', $folio)
                            ->where('idPeriodo', $periodo->idPeriodo)
                            ->where('tipomovto', 'A')
                            ->whereNull('doctoImpreso')
                            ->update([
                                'doctoImpreso' => 1
                            ]);

        }

        if($servicios->metodo =='kardex'){  
            return $this->kardexService->generaReporte($alumno->uid,$alumno->idNivel,$alumno->idCarrera,'AP',$aquaMark);
        }          
        
    }

}