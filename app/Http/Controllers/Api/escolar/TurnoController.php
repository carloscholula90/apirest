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
        $turnos = Turno::create([
                        'idTurno' => $newId,
                        'descripcion' => strtoupper(trim($request->descripcion))
        ]);

        if (!$Turno) 
            return $this->returnEstatus('Error al crear el Turno',500,null); 
        return $this->returnData('$turnos',$turnos,201);   
    }

    public function show($idTurno){
        try {
            $$turnos = Turno::findOrFail($idTurno);
            return $this->returnData('$turnos',$$turnos,200);   
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->returnEstatus('Turno no encontrado',404,null); 
        }
    }
    
    public function destroy($idTurno){
        $Turno = Turno::find($idTurno);

        if (!$Turno) 
            return $this->returnEstatus('Turno no encontrado',404,null);             
        
            $Turno->delete();
        return $this->returnEstatus('Turno eliminado',200,null); 
    }

    public function update(Request $request, $idTurno){

        $Turno = Turno::find($idTurno);
        
        if (!$Turno) 
            return $this->returnEstatus('Turno no encontrado',404,null);             

        $validator = Validator::make($request->all(), [
                    'idTurno' => 'required|numeric|max:255',
                    'descripcion' => 'required|max:255'
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
                                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
            
        if ($request->has('idTurno')) 
            $Turno->idTurno = $request->idTurno;        

        if ($request->has('descripcion')) 
            $Turno->descripcion = strtoupper(trim($request->descripcion));        

        $Turno->save();
        return $this->returnEstatus('Turno actualizado',200,null);    
    }
}
