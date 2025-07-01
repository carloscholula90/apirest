<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TurnoController extends Controller{

    public function index(){       
        $turnos = Turno::all();
        return $this->returnData('turnos',$turnos,200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Turno::max('idTurno');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try{
        $turnos = Turno::create([
                        'idTurno' => $newId,
                        'descripcion' => strtoupper(trim($request->descripcion)),
                        'letra' => strtoupper(trim($request->letra)),
                        'parciales' => $request->parciales
        ]);

        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('El turno ya se encuentra dado de alta',400,null);
            return $this->returnEstatus('Error al insertar el turno',400,null);
        }
        if (!$turnos) 
            return $this->returnEstatus('Error al crear el Turno',500,null); 
        return $this->returnData('$turnos',$turnos,200);   
    }

    public function show($idTurno){
        try {
            $turnos = Turno::findOrFail($idTurno);
            return $this->returnData('$turnos',$turnos,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Turno no encontrado',404,null); 
        }
    }
    
    public function destroy($idTurno){
        $Turno = Turno::find($idTurno);

        if (!$Turno) 
            return $this->returnEstatus('Turno no encontrado',404,null); 
           
        try {
                $Turno->delete();
                return $this->returnEstatus('Turno eliminado',200,null); 
        } catch (QueryException $e) {
        if ($e->getCode() == '23000') {
            // Este es el código de error para integridad referencial
            return $this->returnEstatus('No se puede eliminar el turno ya esta siendo utilizado',400,null); 
        } 
        }
    }

    public function update(Request $request, $idTurno){

        $Turno = Turno::find($idTurno);
        
        if (!$Turno) 
            return $this->returnEstatus('Turno no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'idTurno' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255',
                    'letra' => 'required|max:255',
                    'parciales' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        $Turno->idTurno = $request->idTurno;
        $Turno->descripcion = strtoupper(trim($request->descripcion));
        $Turno->save();
        return $this->returnData('Turno',$Turno,200);
    }

    public function updatePartial(Request $request, $idTurno){

        $Turno = Turno::find($idTurno);
        
        if (!$Turno) 
            return $this->returnEstatus('Turno no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                                    'idTurno' => 'required|numeric|max:255',
                                    'descripcion' => 'required|max:255',
                                    'letra' => 'required|max:255',
                                    'parciales' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idTurno')) 
            $Turno->idTurno = $request->idTurno;        

        if ($request->has('descripcion')) 
            $Turno->descripcion = strtoupper(trim($request->descripcion));  

        if ($request->has('letra')) 
            $Turno->letra = strtoupper(trim($request->letra));  
        
        if ($request->has('parciales')) 
            $Turno->parciales = strtoupper(trim($request->parciales));   

        $Turno->save();
        return $this->returnEstatus('Turno actualizado',200,null);    
    }

    public function generaReporte(){
        return $this->imprimeCtl('turno',' turnos ',['CLAVE', 'DESCRIPCIÓN','LETRA','PARCIALES'],[100,200,100,100],'descripcion');
     } 
 
     public function exportaExcel() {
        return $this->exportaXLS('turno','idTurno',['CLAVE', 'DESCRIPCIÓN','LETRA','PARCIALES'],'descripcion');     
    } 
}
