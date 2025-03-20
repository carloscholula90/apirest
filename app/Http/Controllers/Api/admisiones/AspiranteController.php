<?php

namespace App\Http\Controllers\Api\admisiones; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\admisiones\Aspirante;
use App\Models\general\Persona;  
    
class AspiranteController extends Controller
{
   /**
    * Show the form for creating a new resource.
   */
    public function store(Request $request){   

        $validator = Validator::make($request->all(), [
                            'idNivel' => 'required|numeric',
                            'idPeriodo' => 'required|numeric',
                            'idCarrera' => 'required|numeric',
                            'idTurno' => 'required|numeric',
                            'fechaSolicitud' => 'required|max:255',
                            'primerApellido' => 'required|max:255',
                            'segundoApellido' => 'required|max:255',
                            'nombre' => 'required|max:255',
                            'fechaNacimiento' => 'required|date',  
                            'sexo' => 'required|max:255',
                            'rfc' => 'required|max:255',
                            'curp' => 'required|max:255',
                            'idPais' => 'required|numeric',
                            'idEstado' => 'required|numeric',
                            'idCiudad' => 'required|numeric',
                            'idCp' => 'required|numeric',
                            'idAsentamiento' => 'required|numeric',
                            'noExterior' => 'required|numeric',
                            'noInterior' => 'required|numeric',
                            'calle' => 'required|max:255',
                            //'contactos' => 'required|array',
                            //'familiares' => 'required|array',
                            'idNivelAnterior' =>'required|numeric',
                            'paisCursoGradoAnterior' =>'required|numeric',
                            'estadoCursoGradoAnterior' =>'required|numeric',
                            'escuelaProcedencia' => 'required|max:255',
                            'adeudoAsignaturas' => 'required|numeric|max:1',
                            'matReprobada' =>'required|numeric',
                            'mesReprobada' =>'required|numeric',
                            'publica' =>'required|numeric|max:1'
                            //'alergias' => 'required|array',
                            //'enfermedades' => 'required|array',
                            //'uidEmpleado' =>'required|numeric',
                            //'idMedio' =>  'required|numeric' 
        ]);

        if ($validator->fails()) 
        return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 
        $maxId = Persona::max('uid');  
        $newId = $maxId ? $maxId + 1 : 1;  
        $persona = Persona::create([
                                'uid' => $newId,
                                'curp' => strtoupper(trim($request->curp)),
                                'nombre' => strtoupper(trim($request->nombre)),
                                'primerApellido' => strtoupper(trim($request->primerApellido)),
                                'segundoApellido' => strtoupper(trim($request->segundoApellido)),
                                'fechaNacimiento' => strtoupper(trim($request->fechaNacimiento)),
                                'sexo' => strtoupper(trim($request->sexo)),
                                'idPais' =>$request->idPais,
                                'idEstado' => $request->idEstado,
                                'idCiudad' => $request->idCiudad
                ]);

                if (!$persona) 
                    return $this->returnEstatus('Error al crear a la persona',500,null);         
            
                else {
                     $consecutivo = Aspirante::max('consecutivo')
                                            ->where('uid',$newId);

                     $aspirante = Aspirante::create([
                                'uid' => $newId,
                                'secuencia' => $consecutivo + 1,
                                'idPeriodo' => $request->idPeriodo,
                                'idCarrera' => $request->idCarrera,
                                'adeudoAsignaturas' => $request->adeudoAsignaturas,
                                'idNivel' => $request->idNivel,
                                'idMedio' => $request->idMedio,
                                'publica' => $request->publica,
                                'paisCursoGradoAnterior' => $request->paisCursoGradoAnterior,
                                'estadoCursoGradoAnterior' => $request->estadoCursoGradoAnterior,
                                'uidEmpleado' => $request->uidEmpleado,
                                'fechaSolicitud' => $request->fechaSolicitud,
                                'matReprobada' => $request->matReprobada,
                                'mesReprobada' => $request->mesReprobada,
                                'idNivelAnterior' => $request->idNivelAnterior,
                                'escuelaProcedencia' => $request->escuelaProcedencia]);

                    if (!$aspirante) 
                    return $this->returnEstatus('Error al crear al aspirante',500,null);   
                }
   }     

   
}
