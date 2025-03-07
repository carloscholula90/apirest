<?php

namespace App\Http\Controllers\Api\tesoreria;  
use App\Http\Controllers\Controller;
use App\Models\tesoreria\ProductoServicioSAT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductoServicioSATController extends Controller{

    public function index(){       
        $productoserviciosat = ProductoServicioSAT::all();
        return $this->returnData('productoserviciosat',$productoserviciosat,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = ProductoServicioSAT::max('idProductoServicio');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $productoserviciosat = ProductoServicioSAT::create([
                            'idProductoServicio' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El ProductoServicioSAT ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el ProductoServicioSAT',400,null);
        }

        if (!$productoserviciosat) 
            return $this->returnEstatus('Error al crear el ProductoServicioSAT',500,null); 
        return $this->returnData('$productoserviciosat',$productoserviciosat,201);   
    }

    public function show($idProductoServicio){
        try {
            $productoserviciosat = ProductoServicioSAT::findOrFail($idProductoServicio);
            return $this->returnData('$productoserviciosat',$productoserviciosat,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('ProductoServicioSAT no encontrado',404,null); 
        }
    }
    
    public function destroy($idProductoServicio){
        $ProductoServicioSAT = ProductoServicioSAT::find($idProductoServicio);

        if (!$ProductoServicioSAT) 
            return $this->returnEstatus('ProductoServicioSAT no encontrado',404,null);             
        
            $ProductoServicioSAT->delete();
        return $this->returnEstatus('ProductoServicioSAT eliminado',200,null); 
    }

    public function update(Request $request, $idProductoServicio){

        $ProductoServicioSAT = ProductoServicioSAT::find($idProductoServicio);
        
        if (!$ProductoServicioSAT) 
            return $this->returnEstatus('ProductoServicioSAT no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'idProductoServicio' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $ProductoServicioSAT->idProductoServicio = $request->idProductoServicio;
        $ProductoServicioSAT->descripcion = strtoupper(trim($request->descripcion));
        $ProductoServicioSAT->save();
        return $this->returnData('ProductoServicioSAT',$ProductoServicioSAT,200);
    }

    public function updatePartial(Request $request, $idProductoServicio){

        $ProductoServicioSAT = ProductoServicioSAT::find($idProductoServicio);
        
        if (!$ProductoServicioSAT) 
            return $this->returnEstatus('ProductoServicioSAT no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idProductoServicio' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idProductoServicio')) 
            $ProductoServicioSAT->idProductoServicio = $request->idProductoServicio;        

        if ($request->has('descripcion')) 
            $ProductoServicioSAT->descripcion = strtoupper(trim($request->descripcion));        

        $ProductoServicioSAT->save();
        return $this->returnEstatus('ProductoServicioSAT actualizado',200,null);    
    }

     
    public function generaReporte()
    {
       return $this->imprimeCtl('productoServicioSAT',' productos servicio sat ',null,null,'descripcion');
    } 

    public function exportaExcel() {
       return $this->exportaXLS('productoServicioSAT','idProductoServicio',['CLAVE', 'DESCRIPCIÓN'],'descripcion');     
   }   
}
