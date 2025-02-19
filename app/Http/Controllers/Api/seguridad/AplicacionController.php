<?php

namespace App\Http\Controllers\Api\seguridad;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\seguridad\Aplicacion;
use Illuminate\Support\Facades\Validator;

class AplicacionController extends Controller
{
    public function index()
    {
        $aplicacion = Aplicacion::all();
        return $this->returnData('aplicacion',$aplicacion,200);        
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                            'descripcion' =>'required|max:255',
                            'activo' => 'required|numeric|max:255',
                            'idModulo' => 'required|numeric|max:255'

        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxIdAplicacion = Aplicacion::max('idAplicacion');
        $newIdAplicacion = $maxIdAplicacion ? $maxIdAplicacion + 1 : 1;
      
        $aplicacion = Aplicacion::create([
                                'idAplicacion' =>  $newIdAplicacion,
                                'descripcion' => $request->descripcion,
                                'activo' => $request->activo,
                                'idModulo' => $request->idModulo
        ]);

        if (!$aplicacion) 
            return $this->returnEstatus('Error al crear la aplicacion',500,null);

        $aplicacion = Aplicacion::findOrFail($newIdAplicacion);        
        return $this->returnData('aplicacion',$aplicacion,200);
    }

    public function show($id)
    {
        $aplicacion = Aplicacion::find($id);
        if (!$aplicacion)
            return $this->returnEstatus('Aplicacion no encontrada',404,null);
        return $this->returnData('aplicacion',$aplicacion,200);
    }

    public function destroy($id)
    {
        $aplicacion = Aplicacion::find($id);

        if (!$aplicacion) 
            return $this->returnEstatus('Aplicacion no encontrada',404,null);
        
        $aplicacion->delete();
        return $this->returnEstatus('Aplicacion eliminada',200,null);
    }

    public function update(Request $request, $id)
    {
        $aplicacion = Aplicacion::find($id);

        if (!$aplicacion) 
            return $this->returnEstatus('Aplicacion no encontrada',404,null);

        $validator = Validator::make($request->all(), [
                                    'idAplicacion' => $request->idAplicacion,
                                    'descripcion' => $request->descripcion,
                                    'activo' => $request->activo,
                                    'idModulo' => $request->idModulo
        ]);

        if ($validator->fails()) 
        return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $aplicacion->idAplicacion = $request->idAplicacion;
        $aplicacion->descripcion = strtoupper(trim($request->descripcion));
        $aplicacion->activo = strtoupper(trim($request->activo));
        $aplicacion->idModulo = $request->idModulo;

        $aplicacion->save();
        return $this->returnEstatus('Aplicacion actualizada',200,null);
    }

    public function updatePartial(Request $request, $idAplicacion)
    {
        $aplicacion = Aplicacion::find($idAplicacion);

        if (!$aplicacion) 
            return $this->returnEstatus('Aplicacion no encontrada',404,null);

        $aplicacion->idAplicacion = $idAplicacion;
        

        if ($request->has('descripcion')) 
            $aplicacion->descripcion = strtoupper(trim($request->descripcion));
        
        if ($request->has('activo')) 
            $aplicacion->activo = strtoupper(trim($request->activo));
        

        if ($request->has('idModulo')) 
            $aplicacion->idModulo = $request->idModulo;
        

        $aplicacion->save();
        return $this->returnEstatus('Aplicacion actualizada',200,null);
    }

    public function generaReporte()
    {
       return $this->imprimeCtl('aplicaciones','aplicaciones',['CLAVE','DESCRIPCIÓN','ACTIVO','ID MODULO','ALIAS','ICONO'],[100,300,100,100,100,100],'descripcion');
   }
       
    public function exportaExcel() {  
        return $this->exportaXLS('aplicaciones','idAplicacion', ['CLAVE','DESCRIPCIÓN'],'descripcion');     
    }
}
