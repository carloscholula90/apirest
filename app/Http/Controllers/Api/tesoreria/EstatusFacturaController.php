<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\EstatusFactura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EstatusFacturaController extends Controller{

    public function index(){       
        $estatusfacturas = EstatusFactura::all();
        return $this->returnData('estatusfacturas',$estatusfacturas,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = EstatusFactura::max('idEstatusFactura');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $estatusfacturas = EstatusFactura::create([
                            'idEstatusFactura' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El EstatusFactura ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el EstatusFactura',400,null);
        }

        if (!$estatusfacturas) 
            return $this->returnEstatus('Error al crear el EstatusFactura',500,null); 
        return $this->returnData('$estatusfacturas',$estatusfacturas,200);   
    }

    public function show($idEstatusFactura){
        try {
            $estatusfacturas = EstatusFactura::findOrFail($idEstatusFactura);
            return $this->returnData('$estatusfacturas',$estatusfacturas,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('EstatusFactura no encontrado',404,null); 
        }
    }
    
    public function destroy($idEstatusFactura){
        $EstatusFactura = EstatusFactura::find($idEstatusFactura);

        if (!$EstatusFactura) 
            return $this->returnEstatus('EstatusFactura no encontrado',404,null);             
        
            $EstatusFactura->delete();
        return $this->returnEstatus('EstatusFactura eliminado',200,null); 
    }

    public function update(Request $request, $idEstatusFactura){

        $EstatusFactura = EstatusFactura::find($idEstatusFactura);
        
        if (!$EstatusFactura) 
            return $this->returnEstatus('EstatusFactura no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'idEstatusFactura' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $EstatusFactura->idEstatusFactura = $request->idEstatusFactura;
        $EstatusFactura->descripcion = strtoupper(trim($request->descripcion));
        $EstatusFactura->save();
        return $this->returnData('EstatusFactura',$EstatusFactura,200);
    }

    public function updatePartial(Request $request, $idEstatusFactura){

        $EstatusFactura = EstatusFactura::find($idEstatusFactura);
        
        if (!$EstatusFactura) 
            return $this->returnEstatus('EstatusFactura no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idEstatusFactura' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idEstatusFactura')) 
            $EstatusFactura->idEstatusFactura = $request->idEstatusFactura;        

        if ($request->has('descripcion')) 
            $EstatusFactura->descripcion = strtoupper(trim($request->descripcion));        

        $EstatusFactura->save();
        return $this->returnEstatus('EstatusFactura actualizado',200,null);    
    }

     
    public function generaReporte()
    {
       return $this->imprimeCtl('estatusFactura',' estatus factura ',null,null,'descripcion');
    } 

    public function exportaExcel() {
       return $this->exportaXLS('estatusFactura','idEstatusFactura',['CLAVE', 'DESCRIPCIÓN'],'descripcion');     
   }   
}
