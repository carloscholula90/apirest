<?php

namespace App\Http\Controllers\Api\{ruta};  
use App\Http\Controllers\Controller;
use App\Models\{ruta}\{Nombre};
use Illuminate\Http\Request;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class {Nombre}Controller extends Controller{

    public function index(){       
        ${nameApi} = {Nombre}::all();
        return $this->returnData('{nameApi}',${nameApi},200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = {Nombre}::max('id{Nombre}');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            ${nameApi} = {Nombre}::create([
                            'id{Nombre}' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion))
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El {nombre} ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar el {nombre}',400,null);
        }

        if (!${nameApi}) 
            return $this->returnEstatus('Error al crear el {nombre}',500,null); 
        return $this->returnData('${nameApi}',${nameApi},201);   
    }

    public function show($id{Nombre}){
        try {
            $${nameApi} = {Nombre}::findOrFail($id{Nombre});
            return $this->returnData('${nameApi}',${nameApi},200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('{Nombre} no encontrado',404,null); 
        }
    }
    
    public function destroy($id{Nombre}){
        ${nombre} = {Nombre}::find($id{Nombre});

        if (!${nombre}) 
            return $this->returnEstatus('{Nombre} no encontrado',404,null);             
        
            ${nombre}->delete();
        return $this->returnEstatus('{Nombre} eliminado',200,null); 
    }

    public function update(Request $request, $id{Nombre}){

        ${nombre} = {Nombre}::find($id{Nombre});
        
        if (!${nombre}) 
            return $this->returnEstatus('{Nombre} no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'id{Nombre}' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        ${nombre}->id{Nombre} = $request->id{Nombre};
        ${nombre}->descripcion = strtoupper(trim($request->descripcion));
        ${nombre}->save();
        return $this->returnData('{nombre}',${nombre},200);
    }

    public function updatePartial(Request $request, $id{Nombre}){

        ${nombre} = {Nombre}::find($id{Nombre});
        
        if (!${nombre}) 
            return $this->returnEstatus('{Nombre} no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'id{Nombre}' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('id{Nombre}')) 
            ${nombre}->id{Nombre} = $request->id{Nombre};        

        if ($request->has('descripcion')) 
            ${nombre}->descripcion = strtoupper(trim($request->descripcion));        

        ${nombre}->save();
        return $this->returnEstatus('{Nombre} actualizado',200,null);    
    }

      // Función para generar el reporte de personas
      public function generaReport()
      {
        ${nombre} = $this->{Nombre}::all();
     
         // Si no hay personas, devolver un mensaje de error
         if (${nombre}->isEmpty())
             return $this->returnEstatus('No se encontraron datos para generar el reporte',404,null);
         
         $headers = ['Id', 'descripcion'];
         $columnWidths = [80,100];   
         $keys = ['id{Nombre}', 'descripcion'];
        
         ${nombre}Array = ${nombre}->map(function (${nombre}) {
             return ${nombre}->toArray();
         })->toArray();   
     
         return $this->pdfController->generateReport(${nombre}Array,$columnWidths,$keys , 'REPORTE DE ...', $headers,'L','letter');
       
     }  
}
