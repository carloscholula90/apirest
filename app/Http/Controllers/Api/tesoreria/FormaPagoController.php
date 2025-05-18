<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\FormaPago;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FormaPagoController extends Controller{

    protected $pdfController;

    // Inyección de la clase PdfReportGenerator
    public function __construct(pdfController $pdfController)
    {
        $this->pdfController = $pdfController;
    }


    public function index(){       
        $formaspagos = FormaPago::all();
        return $this->returnData('formaspagos',$formaspagos,200);
    }   

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = FormaPago::max('idFormaPago');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $formaspagos = FormaPago::create([
                            'idFormaPago' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('La forma de pago ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar la forma de pago',400,null);
        }

        if (!$formaspagos) 
            return $this->returnEstatus('Error al crear la forma de pago',500,null); 
        return $this->returnData('formaspagos',$formaspagos,200);   
    }

    public function show($idFormaPago){
        try {
            $$formaspagos = FormaPago::findOrFail($idFormaPago);
            return $this->returnData('formaspagos',$formaspagos,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Forma de pago no encontrado',404,null); 
        }
    }
    
    public function destroy($idFormaPago){
        $formasPagos = FormaPago::find($idFormaPago);

        if (!$formasPagos) 
            return $this->returnEstatus('Forma de pago no encontrado',404,null);             
        
            $formasPagos->delete();
        return $this->returnEstatus('Forma de pago eliminado',200,null); 
    }

    public function update(Request $request, $idFormaPago){

        $formasPagos = FormaPago::find($idFormaPago);
        
        if (!$formasPagos) 
            return $this->returnEstatus('FormaPago no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'idFormaPago' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $formasPagos->idFormaPago = $request->idFormaPago;
        $formasPagos->descripcion = strtoupper(trim($request->descripcion));
        $formasPagos->save();
        return $this->returnData('FormaPago',$formasPagos,200);
    }

    public function updatePartial(Request $request, $idFormaPago){

        $formasPagos = FormaPago::find($idFormaPago);
        
        if (!$formasPagos) 
            return $this->returnEstatus('Forma de pago no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idFormaPago' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idFormaPago')) 
            $formasPagos->idFormaPago = $request->idFormaPago;        

        if ($request->has('descripcion')) 
            $formasPagos->descripcion = strtoupper(trim($request->descripcion));        

        $formasPagos->save();
        return $this->returnEstatus('FormaPago actualizado',200,null);    
    }

    public function getFormaPago()
    {
        // Realizar la consulta y devolver los resultados
        $formasPagos = FormaPago::select(
                'idFormaPago',
                'descripcion',
                'solicita4digitos'
            )
            ->get();
        return $formasPagos;
    }

      // Función para generar el reporte de personas
      public function generaReport()
      {
        $formasPagos = $this->getFormaPago();  
     
         // Si no hay personas, devolver un mensaje de error
         if ($formasPagos->isEmpty())
             return $this->returnEstatus('No se encontraron datos para generar el reporte',404,null);
         
         $headers = ['ID', 'DESCRIPCION'];
         $columnWidths = [80,500];   
         $keys = ['idFormaPago', 'descripcion'];
        
         $formasPagosArray = $formasPagos->map(function ($formasPagos) {
             return $formasPagos->toArray();
         })->toArray();   
     
         return $this->pdfController->generateReport($formasPagosArray,$columnWidths,$keys , 'REPORTE DE FORMA DE PAGO', $headers,'L','letter');
       
     }  

     public function exportaExcel() {
        return $this->exportaXLS('formaPago','idFormaPago',['CLAVE', 'DESCRIPCIÓN'],'descripcion');     
    }
}
