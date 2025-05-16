<?php

namespace App\Http\Controllers\Api\escolar; 

use Illuminate\Http\Request;
use App\Models\escolar\Detasignatura;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; 

class DetasignaturaController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $detalle = Detasignatura::all();
        return $this->returnData('detalle',$detalle,200);
    }

     public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
                                        'idPlan' =>'required|max:255',
                                        'idCarrera' => 'required|max:255',
                                        'idAsignatura' =>'required|max:255',
                                        'ordenk' =>'required|max:255',   
                                        'semestre' =>'required|max:255',
                                        'ordenc' =>'required|numeric|max:255',
                                        'condocente' =>'required|numeric|max:255',
                                        'independientes' =>'required|numeric|max:255',
                                        'creditos' =>'required|numeric|max:255'
        ]);   

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
    
        $existe = Detasignatura::where('idPlan', $request->idPlan)
                                ->where('idCarrera', $request->idCarrera)
                                ->where('idAsignatura', $request->idAsignatura)
                                ->exists();
        
        if ($existe)
           return $this->returnEstatus('Ya existe la asignatura '.$request->idAsignatura.' en el plan '.$request->idPlan,500,null);

        $existe = Detasignatura::where('idPlan', $request->idPlan)
                                ->where('idCarrera', $request->idCarrera)
                                ->where('ordenk', $request->ordenk)
                                ->exists();
        
         if ($existe)
           return $this->returnEstatus('Ya existe el orden',500,null);

        if($request->seriacion!=null) {

            if($request->seriacion == $request->idAsignatura)
                 return $this->returnEstatus('No se puede usar la misma asignatura con seriaciòn',500,null);

            $existe = Detasignatura::where('idPlan', $request->idPlan)
                                ->where('idCarrera', $request->idCarrera)
                                ->where('seriacion', $request->seriacion)
                                ->exists();
        
             if ($existe)
                return $this->returnEstatus('Ya existe la asignatura en el plan',500,null);
        }       

        $existe = Detasignatura::where('idPlan', $request->idPlan)
                                ->where('idCarrera', $request->idCarrera)
                                ->where('ordenc', $request->ordenc)
                                ->exists();
        
        if ($existe) 
           return $this->returnEstatus('Ya existe el orden en el plan',500,null);

        $maxSeq = Detasignatura::max('secPlan');
        $nextSeq = ($maxSeq === null) ? 1 : $maxSeq + 1;
                           
        try {
            $periodos = Detasignatura::create([
                                        'secPlan'  => $nextSeq,
                                        'idPlan' => $request->idPlan,
                                        'idCarrera' => $request->idCarrera,
                                        'idAsignatura' => $request->idAsignatura,
                                        'ordenk' => $request->ordenk,
                                        'semestre' => $request->semestre,
                                        'ordenc' => $request->ordenc,
                                        'condocente' => $request->condocente,
                                        'independientes' => $request->independientes,
                                        'creditos' => $request->creditos
                        ]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') 
                return $this->returnEstatus('Error ya existe',400,null);
            return $this->returnEstatus('Error al insertar',400,null);
        }  

        if (!$periodos) 
            return $this->returnEstatus('Error al crear el detalle',500,null); 
        return $this->returnData('$periodos',$periodos,201);   
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $secPlan)
    {
         $deletedRows = Detasignatura::where('secPlan', $secPlan)
                       ->delete();
        return $this->returnEstatus('Registro eliminado eliminado',200,null); 
    }
}
