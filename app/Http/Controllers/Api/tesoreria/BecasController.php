<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\Beca;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BecasController extends Controller{

    protected $pdfController;

    // Inyección de la clase PdfReportGenerator
    public function __construct(pdfController $pdfController)
    {
        $this->pdfController = $pdfController;
    }


    public function index(){       
        $becas = Beca::all();
        return $this->returnData('becas',$becas,200);
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

    public function show($idBeca){
        try {
            $$becas = Beca::findOrFail($idBeca);
            return $this->returnData('becas',$becas,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Beca no encontrado',404,null); 
        }
    }
    
    public function destroy($idBeca){
        $becas = Beca::find($idBeca);

        if (!$becas) 
            return $this->returnEstatus('Beca no encontrado',404,null);             
        
            $becas->delete();
        return $this->returnEstatus('Beca eliminado',200,null); 
    }

    public function update(Request $request, $idBeca){

        $becas = Beca::find($idBeca);
        
        if (!$becas) 
            return $this->returnEstatus('Beca no encontrado',404,null);             

        $validator = Validator::make($request->all(), [ 'idBeca' => 'required|numeric|max:255',
                                                        'descripcion' => 'required|max:255',
                                                        'aplicaInscripcion' => 'required|max:255',
                                                        'aplicaColegiatura' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $becas->idBeca = $request->idBeca;
        $becas->descripcion = strtoupper(trim($request->descripcion));         
        $becas->aplicaInscripcion = $request->aplicaInscripcion;
        $becas->aplicaColegiatura = $request->aplicaColegiatura;
        $becas->fechaModificacion = Carbon::now(); 

        $becas->save();
        return $this->returnData('Beca',$becas,200);
    }

      // Función para generar el reporte de personas
      public function generaReport()
      {
        $becas = $this->getFormaPago();  
     
         // Si no hay personas, devolver un mensaje de error
         if ($becas->isEmpty())
             return $this->returnEstatus('No se encontraron datos para generar el reporte',404,null);
         
         $headers = ['ID', 'DESCRIPCION','APLICA INSCRIPCION','APLICA COLEGIATURA','FCH ALTA','FCH MODIFICACION'];
         $columnWidths = [80,500,100,100,100,100];   
         $keys = ['idBeca', 'descripcion','aplicaInscripcion','aplicaColegiatura','fechaAlta','fechaModificacion'];
        
         $formasPagosArray = $becas->map(function ($becas) {
             return $becas->toArray();
         })->toArray();   
     
         return $this->pdfController->generateReport($formasPagosArray,$columnWidths,$keys , 'REPORTE DE BECAS', $headers,'L','letter');
       
     }  

     public function exportaExcel() {
        return $this->exportaXLS('Beca','idBeca',['ID', 'DESCRIPCION','APLICA INSCRIPCION','APLICA COLEGIATURA','FCH ALTA','FCH MODIFICACION'],'descripcion');     
    }
}
