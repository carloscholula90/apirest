<?php

namespace App\Http\Controllers\Api\admisiones; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\admisiones\Aspirante;
use App\Models\general\Persona;  
use App\Models\general\Alergia;
use App\Models\general\Integra;
use App\Models\general\Salud;
use App\Models\general\Contacto;
use App\Models\general\Familia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;     
    
class AspiranteController extends Controller{
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
                            'contactos' => 'required|array',
                            'familias' => 'required|array',
                            'idNivelAnterior' =>'required|numeric',
                            'paisCursoGradoAnterior' =>'required|numeric',
                            'estadoCursoGradoAnterior' =>'required|numeric',
                            'escuelaProcedencia' => 'required|max:255',
                            'adeudoAsignaturas' => 'required|numeric|max:1',
                            'matReprobada' =>'required|numeric',
                            'mesReprobada' =>'required|numeric',
                            'publica' =>'required|numeric|max:1',  
                            'uidEmpleado' =>'required|numeric',
                            'idMedio' =>  'required|numeric' 
        ]);
        
        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        //Agregamos el registro de integra
        $maxId = Persona::max('uid');  
        $newId = $maxId ? $maxId + 1 : 1;  
        $persona = Persona::create([
                                'uid' => $newId,
                                'curp' => strtoupper(trim($request->curp)),
                                'nombre' => strtoupper(trim($request->nombre)),
                                'primerApellido' => strtoupper(trim($request->primerApellido)),
                                'segundoApellido' => strtoupper(trim($request->segundoApellido)),
                                'fechaNacimiento' => $request->fechaNacimiento,
                                'sexo' => strtoupper(trim($request->sexo)),
                                'idPais' =>$request->idPais,
                                'idEstado' => $request->idEstado,
                                'idCiudad' => $request->idCiudad
                ]);

                if (!$persona) 
                    return $this->returnEstatus('Error al crear a la persona',500,null);         
            
                else {
                        $maxSeq= Integra::where('uid', $newId)->where('idRol',3)->max('secuencia');
                        $maxSeq = isset($maxSeq) ? $maxSeq + 1: 1;    
                        $integra = Integra::create(['uid' => $newId,'secuencia' =>$maxSeq,'idRol'=> 3]);
                        
                        Log::info('uid:'.$newId);  
                        Log::info('secuencia:'.$maxSeq);
                        Log::info('idPeriodo:'.$request->idPeriodo);
                        Log::info('idCarrera:'.$request->idCarrera);
                        Log::info('adeudoAsignaturas:'.$request->adeudoAsignaturas);
                        Log::info('idNivel:'.$request->idNivel);
                        Log::info('idMedio:'.$request->idMedio);
                        Log::info('publica:'.$request->publica);
                        Log::info('paisCursoGradoAnterior:'.$request->paisCursoGradoAnterior);
                        Log::info('estadoCursoGradoAnterior:'.$request->estadoCursoGradoAnterior);
                        Log::info('uidEmpleado:'.$request->uidEmpleado);
                        Log::info('fechaSolicitud:'.$request->fechaSolicitud);
                        Log::info('matReprobada:'.$request->matReprobada);        
                        Log::info('mesReprobada:'.$request->mesReprobada);        
                        Log::info('idNivelAnterior:'.$request->idNivelAnterior);        
                        Log::info('escuelaProcedencia:'.$request->escuelaProcedencia);        

                        $aspirante = Aspirante::create([
                                                'uid' => $newId,
                                                'secuencia' => $maxSeq,
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
                                                'escuelaProcedencia' => $request->escuelaProcedencia,
                                                'observaciones'=> '']);  

                        if (!$aspirante) 
                            return $this->returnEstatus('Error al crear al aspirante',500,null);   
                        else {
                            if(isset($request->alergias))
                            foreach ($request->alergias as $alergia) {
                                $maxSeq = Alergia::where('uid', $newId)->max('consecutivo');
                                $nextSeq = ($maxSeq === null) ? 1 : $maxSeq + 1;
                            
                                Alergia::create([
                                    'uid' => $newId,
                                    'consecutivo' => $nextSeq,
                                    'alergia' => strtoupper(trim($alergia)) 
                                ]);
                            }
                            
                            if(isset($request->enfermedades))
                            foreach ($request->enfermedades as $enfermedadData) {
                                $maxSeq = Salud::where('uid', $newId)->max('consecutivo');
                                $nextSeq = ($maxSeq === null) ? 1 : $maxSeq + 1;
                                
                                Salud::create(['uid' => $newId,
                                               'consecutivo' => $nextSeq,
                                               'enfermedad' => strtoupper(trim($enfermedadData['enfermedad'])),  
                                               'medico' => $enfermedadData['medico'],  
                                               'telefono' => $enfermedadData['telefono']]);
                            }
                                    
                            if(isset($request->contactos))
                            foreach ($request->contactos as $contactosData) {
                                $maxSeq = Contacto::where('uid', $newId)
                                                        ->where('idParentesco', 0)
                                                        ->where('idTipoContacto',$contactosData['idTipoContacto'])
                                                        ->where('uid', $newId)
                                                        ->max('consecutivo');
                               
                                                        $nextSeq = ($maxSeq === null) ? 1 : $maxSeq + 1;
                                Log::info('contacto:'.$contactosData['dato']);
                                Contacto::create([  'uid' => $newId,
                                                    'consecutivo' => $nextSeq,
                                                    'idParentesco' => 0,
                                                    'idTipoContacto' => $contactosData['idTipoContacto'],  
                                                    'dato' => strtoupper(trim($contactosData['dato']))
                                            ]);                                               
                            }

                            if(isset($request->familias))
                            foreach ($request->familias as $familiasData) {                                
                                Log::info('familia:'.$familiasData['nombre']);
                               
                                Familia::create([  'uid' => $newId,
                                                   'idParentesco' => $familiasData['idParentesco'],
                                                    'tutor' => $familiasData['tutor'],
                                                    'nombre' => strtoupper(trim($familiasData['nombre'])),
                                                    'primerApellido' => strtoupper(trim($familiasData['primerApellido'])),
                                                    'segundoApellido' => strtoupper(trim($familiasData['segundoApellido'])),
                                                    'fechaNacimiento' => $familiasData['fechaNacimiento'],  
                                                    'finado' => $familiasData['finado']                                                    
                                            ]);                        
                            }
                            return $this->returnEstatus('Registro guardado',200,null); 
                    }
                }
   }     

   
}
