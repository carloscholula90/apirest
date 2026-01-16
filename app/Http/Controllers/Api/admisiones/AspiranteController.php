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
use App\Models\general\DetMedio;  
use App\Models\general\Direccion;
use App\Http\Controllers\Api\serviciosGenerales\pdfController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;    
use App\Http\Controllers\Api\serviciosGenerales\CustomTCPDF; 
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
                            'noExterior' => 'required|max:255',
                            'calle' => 'required|max:255',
                            'contactos' => 'required|array',
                            'familias' => 'required|array',
                            'idNivelAnterior' =>'required|numeric',
                            'paisCursoGradoAnterior' =>'required|numeric',
                            'estadoCursoGradoAnterior' =>'required|numeric',
                            'escuelaProcedencia' => 'required|max:255',
                            'publica' =>'required|numeric|max:1',  
                            'uidEmpleado' =>'required|numeric',
                            'medios' =>  'required|array' 
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
                        $secuencialPers = isset($maxSeq) ? $maxSeq + 1: 1;    
                        $integra = Integra::create(['uid' => $newId,'secuencia' =>$secuencialPers,'idRol'=> 3]);
                        
                        /*Log::info('uid:'.$newId);  
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
                        Log::info('escuelaProcedencia:'.$request->escuelaProcedencia); */       

                        $aspirante = Aspirante::create([
                                                'uid' => $newId,
                                                'secuencia' => $secuencialPers,
                                                'idPeriodo' => $request->idPeriodo,
                                                'idCarrera' => $request->idCarrera,
                                                'adeudoAsignaturas' => $request->adeudoAsignaturas,
                                                'idNivel' => $request->idNivel,
                                                'idTurno' => $request->idTurno,
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
                                                'semestreIngreso'=> $request->semestre,
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

                            Direccion::create([
                                    'uid'=>$newId,
                                    'idParentesco'=>0,
                                    'idTipoDireccion'=>1, //Direccion Principal
                                    'consecutivo'=>1,
                                    'idPais'=>$request->idPais,
                                    'idEstado'=>$request->idEstado,
                                    'idCiudad'=>$request->idCiudad,
                                    'idCp'=>$request->idCp,
                                    'calle'=>$request->calle,
                                    'noExterior'=>$request->noExterior,
                                    'noInterior'=>$request->noInterior
                                ]);
                            
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
                                                    'dato' => trim($contactosData['dato'])
                                            ]);                                               
                            }
                            $index=2;

                            if(isset($request->familias))
                            foreach ($request->familias as $familiasData) {                                
                                Log::info('familia:'.$familiasData['nombre']);
                               
                                Familia::create([
                                                'uid' => $newId,
                                                'idParentesco' => $familiasData['idParentesco'],
                                                'tutor' => $familiasData['tutor'],
                                                'ocupacion' => $familiasData['ocupacion'],
                                                'nombre' => strtoupper(trim($familiasData['nombre'])),
                                                'primerApellido' => strtoupper(trim($familiasData['primerApellido'])),
                                                'segundoApellido' => strtoupper(trim($familiasData['segundoApellido'])),
                                                'fechaNacimiento' => $familiasData['fechaNacimiento'],  
                                                'finado' => $familiasData['finado']                                                    
                                            ]); 

                                Direccion::create([
                                                'uid'=>$newId,
                                                'idParentesco'=>$familiasData['idParentesco'],
                                                'idTipoDireccion'=>1, //Direccion Principal
                                                'consecutivo'=>$index,
                                                'idPais'=>$familiasData['idPais'],
                                                'idEstado'=>$familiasData['idEstado'],
                                                'idCiudad'=>$familiasData['idCiudad'],
                                                'idCp'=>$familiasData['idCp'],
                                                'noExterior'=>$familiasData['noExterior'],
                                                'noInterior'=>$familiasData['noInterior'],
                                                'calle'=>$familiasData['calle']
                                            ]);
                                            $index= $index + 1;
                                                           
                            }


                            if(isset($request->medios))
                            foreach ($request->medios as $medio) {  
                                   
                                DetMedio::create([ 'uid' => $newId,
                                                   'idRol'=>3, 
                                                   'idMedio' => $medio,
                                                   'secuencia' => $secuencialPers                                                
                                            ]);                          
                            }
                            return response()->json([
                                'status' => 200,
                                'uid' => $newId
                                ]);  
                    }
                }
   }  
   
   public function obtenerAspirantes($uid){
    return DB::table('persona')   
            ->select([
                        'persona.uid', 
                        'persona.primerApellido', 
                        'persona.segundoApellido', 
                        'persona.nombre as nombreAsp',  
                        'persona.fechaNacimiento', 
                        'persona.curp', 
                        'persona.rfc', 
                        'pais.nacionalidad',
                        'correo.dato as correo', 
                        'telefono.dato as telefono', 
                        'telefonoTutor.dato as telefonoTutor', 
                        'nivel.descripcion as nivel',
                        'nivel.idNivel',
                        'familia.nombre as nombreTutor', 
                        'familia.primerApellido as primerApellidoTutor',
                        'familia.segundoApellido as segundoApellidoTutor', 
                        'familia.ocupacion',
                        'aspirante.escuelaProcedencia', 
                        'aspirante.publica', 
                        'aspirante.uidEmpleado', 
                        'aspirante.fechaSolicitud',
                        'direcciones.calle', 
                        'codigoPostal.cp',
                        'codigoPostal.descripcion as colonia',
                        'direcciones.noExterior', 
                        'direcciones.noInterior',
                        'ciudadAsp.descripcion as ciudad', 
                        'estado.descripcion as estado', 
                        'direcciones.idCp', 
                        'periodo.idPeriodo as idPeriodo',
                        'periodo.descripcion as periodo',
                        'aspirante.observaciones',
                        'gradoAnt.descripcion as edoGradoAnt',
                        'paisAsp.descripcion as pais',
                        'aspirante.estadoCursoGradoAnterior',
                        'aspirante.secuencia',
                        'carrera.descripcion as carrera',
                        'carrera.idCarrera',
                        'aspirante.idTurno',
                        'turno.descripcion as turno',
                        'aspirante.semestreIngreso as semestre',
                        DB::raw('CONCAT(direccionTutor.calle, " ", direccionTutor.noExterior, " ", direccionTutor.noInterior," ",
                                 cpTutor.descripcion, " ",cpTutor.cp) AS direccionTutor'),                        
                         DB::raw('CONCAT(persona.primerApellido, " ", persona.segundoApellido, " ", persona.nombre) AS nombre'),
                         DB::raw('CONCAT(asesor.primerApellido, " ", asesor.segundoApellido, " ", asesor.nombre) AS nombreAsesor'),
                         DB::raw('CASE WHEN persona.sexo ="F" THEN "FEMENINO" ELSE "MASCULINO" END AS sexo'),
                         DB::raw('TIMESTAMPDIFF(YEAR,persona.fechaNacimiento, CURDATE()) as edad'),
                         DB::raw('CASE WHEN aspirante.publica = 1 THEN "PUBLICA" ELSE "PRIVADA" END AS publica')
                    ])
                    ->join('aspirante', 'persona.uid', '=', 'aspirante.uid')
                   
                    ->join('carrera', 'carrera.idCarrera', '=', 'aspirante.idCarrera')
                    ->join('turno', 'turno.idTurno', '=', 'aspirante.idTurno')
                    ->join('persona AS asesor', 'asesor.uid', '=', 'aspirante.uidEmpleado')
                    ->leftJoin('estado as gradoAnt', function($join) {
                        $join->on('gradoAnt.idEstado', '=', 'aspirante.estadoCursoGradoAnterior')
                             ->on('gradoAnt.idPais', '=', 'aspirante.paisCursoGradoAnterior');
                    })    
                    ->leftJoin('ciudad', function($join) {
                        $join->on('ciudad.idCiudad', '=', 'persona.idCiudad')
                            ->on('ciudad.idEstado', '=', 'persona.idEstado')
                            ->on('ciudad.idPais', '=', 'persona.idPais');
                    })
                    ->leftJoin('pais', 'pais.idPais', '=', 'persona.idPais')  
                    ->leftJoin('contacto as correo', function($join) {
                        $join->on('correo.uid', '=', 'persona.uid')
                            ->where('correo.idParentesco', 0)
                            ->where('correo.idTipoContacto', 2);
                    })
                    ->leftJoin('contacto as telefono', function($join) {
                        $join->on('telefono.uid', '=', 'persona.uid')
                            ->where('telefono.idParentesco', 0)
                            ->where('telefono.idTipoContacto', 1);
                    })
                    ->leftJoin('nivel', 'nivel.idNivel', '=', 'aspirante.idNivel')
                    ->leftJoin('familia', function($join) {
                        $join->on('familia.uid', '=', 'aspirante.uid')
                            ->where('familia.tutor', 1);
                    })
                    ->leftJoin('direcciones as direccionTutor', function($join) {
                        $join->on('direccionTutor.uid', '=', 'persona.uid')
                            ->where('direccionTutor.idTipoDireccion', 1)
                            ->where('direccionTutor.idParentesco', 'idParentesco.idParentesco');
                    })
                    ->leftJoin('codigoPostal as cpTutor', function($join) {
                        $join->on('direccionTutor.idCp', '=', 'cpTutor.idCp')
                            ->on('cpTutor.idCiudad', '=', 'direccionTutor.idCiudad')
                            ->on('cpTutor.idEstado', '=', 'direccionTutor.idEstado')
                            ->on('cpTutor.idPais', '=', 'direccionTutor.idPais');
                    })
                    ->leftJoin('contacto as telefonoTutor', function($join) {
                        $join->on('telefonoTutor.uid', '=', 'aspirante.uid')
                            ->where('telefonoTutor.idParentesco', 'familia.idParentesco')
                            ->where('telefonoTutor.idTipoContacto', 1);
                    })
                    ->leftJoin('direcciones', function($join) {
                        $join->on('direcciones.uid', '=', 'persona.uid')
                            ->where('direcciones.idTipoDireccion', 1)
                            ->where('direcciones.idParentesco', 0);
                    })
                    ->leftJoin('codigoPostal', function($join) {
                        $join->on('direcciones.idCp', '=', 'codigoPostal.idCp')
                            ->on('codigoPostal.idCiudad', '=', 'direcciones.idCiudad')
                            ->on('codigoPostal.idEstado', '=', 'direcciones.idEstado')
                            ->on('codigoPostal.idPais', '=', 'direcciones.idPais');
                    })
                    ->leftJoin('ciudad as ciudadAsp', function($join) {
                        $join->on('ciudadAsp.idCiudad', '=', 'direcciones.idCiudad')
                            ->on('ciudadAsp.idEstado', '=', 'direcciones.idEstado')
                            ->on('ciudadAsp.idPais', '=', 'direcciones.idPais');
                    })
                    ->leftJoin('periodo as periodo', 'periodo.idPeriodo', '=', 'aspirante.idPeriodo')
                    ->leftJoin('pais as paisAsp', 'paisAsp.idPais', '=', 'ciudadAsp.idPais')
                    ->leftJoin('estado', function($join) {
                        $join->on('estado.idEstado', '=', 'direcciones.idEstado')
                             ->on('estado.idPais', '=', 'direcciones.idPais');
                    })
                    ->leftJoin('alumno', function($join) {
                        $join->on('alumno.uid', '=', 'aspirante.uid')
                            ->on('aspirante.idNivel', '=', 'alumno.idNivel');
                    })
                    ->when(isset($uid), function ($query) use ($uid) {
                        return $query->where('aspirante.uid', $uid);
                    }) 
                    ->whereNull('alumno.uid')
                    ->distinct()
                    ->get() ;    

   }

   public function index(){
    $results = $this->obtenerAspirantes(null);
        // Si no hay personas, devolver un mensaje de error
        if(empty($results)){
            return response()->json([
                'status' => 500,
                'message' => 'No hay datos para generar el reporte'
            ]);
        }  
     
        // Convertir los datos a un formato de arreglo asociativo
        $dataArray = $results->map(function ($item) {
            return (array) $item;
        })->toArray();

        $keys = ['uid','nombre','curp','correo','telefono','nivel'];
        $columnWidths = [70,200,130,150,100,100];         
        $headers = ['UID','NOMBRE','CURP','CORREO','TELEFONO','NIVEL'];
       
        $pdfController = new pdfController();
            
        return $pdfController->generateReport($dataArray,$columnWidths,$keys , 'REPORTE DE ASPIRANTES', $headers,'L','letter',
                                'rptAspirantes'.mt_rand(100, 999).'.pdf');

    }

    public function index2(){
        $results = $this->obtenerAspirantes(null);
            // Si no hay personas, devolver un mensaje de error
            if(empty($results)){
                return response()->json([
                    'status' => 500,
                    'message' => 'No hay datos para generar el reporte'
                ]);
            } 
            else return response()->json([
                'status' => 200,
                'aspirantes' => $results
                ]); 
        }

        
    public function generaReporte($uid){

        $results = $this->obtenerAspirantes($uid);
        
        $data= $results->map(function ($item) {
             return (array) $item; // Convertir cada stdClass a un arreglo
         })->toArray();       
     
        // Rutas de las imágenes para el encabezado y pie
        $imagePathEnc = public_path('images/encPag.png');
        $imagePathPie = public_path('images/piePag.png');
           // Crear una nueva instancia de CustomTCPDF (extendido de TCPDF)
        $pdf = new CustomTCPDF('P', PDF_UNIT, 'letter', true, 'UTF-8', false);
           
        // Configurar los encabezados, las rutas de las imágenes y otros parámetros
        $pdf->setHeaders(null,null,'');
        $pdf->setImagePaths($imagePathEnc, $imagePathPie,'L');
           
        // Configurar las fuentes
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetCreator(PDF_CREATOR);     
        $pdf->SetAuthor('SIAWEB');
           
        // Establecer márgenes y auto-rotura de página
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();

        // Establecer fuente para el cuerpo del documento
        $pdf->SetFont('helvetica', '', 8);
        // Generar la tabla HTML para los datos
        $html2 = '<table cellpadding="1" style="text-align: center;vertical-align: middle;border-collapse: separate; border-spacing: 3px;">';
        $generalesRow = $data[0];
   
           $html2 .= '<tr><td style="font-size: 24px; width: 411px;"><b>Solicitud de Inscripción</b></td>
                        <td><table border-collapse: separate; border-spacing: 3px; cellpadding="1" style="font-size: 10px;"><tr><td  style="width: 100px; height: 15px; background-color: lightgray;border: 1px solid black;">UID</td></tr><tr><td style="width: 100px; height: 15px; border: 1px solid black;margin-bottom: 18px;">'.$generalesRow['uid'].'</td></tr></table></td>';
           $html2 .='</tr>'; 
           $html2 .= '<tr><td style="font-size: 10px;width: 170px; height: 15px; background-color: lightgray;border: 1px solid black;">Apellido paterno</td>
                        <td style="font-size: 10px;width: 170px; height: 15px; background-color: lightgray;border: 1px solid black;">Apellido materno</td>
                        <td style="font-size: 10px;width: 170px; height: 15px; background-color: lightgray;border: 1px solid black;">Nombre</td>
                        </tr>';     
            $html2 .='<tr><td style="font-size: 10px;width: 170px; height: 15px; border: 1px solid black;">'.$generalesRow['primerApellido'].'</td>  
                        <td style="font-size: 10px;width: 170px; height: 15px; border: 1px solid black;">'.$generalesRow['segundoApellido'].'</td>
                        <td style="font-size: 10px;width: 170px; height: 15px; border: 1px solid black;">'.$generalesRow['nombreAsp'].'</td>
                        </tr>';    
            $html2 .='<tr>
                        <td style="text-align: left;font-size: 10px;width: 130px; height: 15px; background-color: lightgray;border: 1px solid black;"> Fecha de nacimiento</td>
                        <td style="text-align: left;font-size: 10px;width: 100px; height: 15px; border: 1px solid black;">'.$generalesRow['fechaNacimiento'].'</td>  
                        <td style="text-align: center;font-size: 10px;width: 50px; height: 15px; background-color: lightgray;border: 1px solid black;">Edad</td>                      
                        <td style="text-align: left;font-size: 10px;width: 54px; height: 15px; border: 1px solid black;">'.$generalesRow['edad'].'</td>
                        <td style="text-align: center;font-size: 10px;width: 50px; height: 15px; background-color: lightgray;border: 1px solid black;">Sexo</td>
                        <td style="text-align: left;font-size: 10px;width: 117px; height: 15px; border: 1px solid black;">'.$generalesRow['sexo'].'</td>  
                    </tr>';
            $html2 .='<tr>
                    <td style="text-align: left;font-size: 10px;width: 130px; height: 15px; background-color: lightgray;border: 1px solid black;"> Nacionalidad</td>
                    <td style="text-align: left;font-size: 10px;width: 152px; height: 15px; border: 1px solid black;">'.$generalesRow['nacionalidad'].'</td>  
                    <td style="text-align: center;font-size: 10px;width: 100px; height: 15px; background-color: lightgray;border: 1px solid black;">Edo. donde nació</td>                      
                    <td style="text-align: left;font-size: 10px;width: 126px; height: 15px; border: 1px solid black;">'.$generalesRow['estado'].'</td>
                </tr>';
            $html2 .='<tr>
                <td style="text-align: left;font-size: 10px;width: 100px; height: 15px; background-color: lightgray;border: 1px solid black;"> CURP</td>
                <td style="text-align: left;font-size: 10px;width: 414px; height: 15px; border: 1px solid black;">'.$generalesRow['curp'].'</td>  
                </tr>';
            $html2 .='<tr>
                <td style="text-align: left;font-size: 10px;width: 100px; height: 15px; background-color: lightgray;border: 1px solid black;"> Dirección</td>
                <td style="text-align: left;font-size: 10px;width: 414px; height: 15px; border: 1px solid black;">'.$generalesRow['calle'].(isset($generalesRow['noExterior'])?' No. exterior '.$generalesRow['noExterior']:'').' '.(isset($generalesRow['noInterior'])?' No. Interior '.$generalesRow['noInterior']:'').'</td>  
                </tr>';
            $html2 .='<tr>
                <td style="text-align: left;font-size: 10px;width: 100px; height: 15px; background-color: lightgray;border: 1px solid black;"> Colonia</td>
                <td style="text-align: left;font-size: 10px;width: 200px; height: 15px; border: 1px solid black;">'.$generalesRow['colonia'].'</td>  
                <td style="text-align: center;font-size: 10px;width: 100px; height: 15px; background-color: lightgray;border: 1px solid black;">Código Postal</td>
                <td style="text-align: left;font-size: 10px;width: 108px; height: 15px; border: 1px solid black;">'.$generalesRow['cp'].'</td>  
                </tr>';
            $html2 .='<tr>
                <td style="text-align: center;font-size: 10px;width: 50px; height: 15px; background-color: lightgray;border: 1px solid black;">Estado</td>                      
                <td style="text-align: left;font-size: 10px;width: 200px; height: 15px; border: 1px solid black;">'.$generalesRow['estado'].'</td>
                <td style="text-align: center;font-size: 10px;width: 60px; height: 15px; background-color: lightgray;border: 1px solid black;">Teléfono</td>
                <td style="text-align: left;font-size: 10px;width: 197px; height: 15px; border: 1px solid black;">'.$generalesRow['telefono'].'</td>
            </tr>';
            $html2 .='<tr>
                <td style="text-align: left;font-size: 10px;width: 150px; height: 15px; background-color: lightgray;border: 1px solid black;"> Correo electrónico</td>
                <td style="text-align: left;font-size: 10px;width: 364px; height: 15px; border: 1px solid black;">'.$generalesRow['correo'].'</td>  
                </tr>';
            $html2 .='<tr>
                <td style="text-align: left;font-size: 10px;width: 70px; height: 15px; background-color: lightgray;border: 1px solid black;"> Escolaridad</td>
                <td style="text-align: left;font-size: 10px;width: 110px; height: 15px; border: 1px solid black;">'.$generalesRow['nivel'].'</td>  
                <td style="text-align: center;font-size: 10px;width: 100px; height: 15px;">'.$generalesRow['publica'].'</td>    
                <td style="text-align: center;font-size: 10px;width: 118px; height: 15px; background-color: lightgray;border: 1px solid black;">Edo. donde lo cursó</td>
                <td style="text-align: left;font-size: 10px;width: 106px; height: 15px; border: 1px solid black;">'.$generalesRow['edoGradoAnt'].'</td>
            </tr>';
            $html2 .='<tr>
                <td style="text-align: left;font-size: 10px;width: 200px; height: 15px; background-color: lightgray;border: 1px solid black;"> Nombre de la institución</td>
                <td style="font-size: 10px;width: 314px; height: 15px; border: 1px solid black;">'.$generalesRow['escuelaProcedencia'].'</td>  
                </tr>';

        $enfermedad = 'NO';

        if (Salud::where('uid', $generalesRow['uid'])->exists()) {
            $enfermedad = 'SI';
        }
            $html2 .='<tr>
                <td style="text-align: left;font-size: 10px;width: 200px; height: 15px; background-color: lightgray;border: 1px solid black;"> ¿Sufre alguna enfermedad crónica?</td>
                <td style="text-align: center;font-size: 10px;width: 30px; height: 15px; border: 1px solid black;">'.$enfermedad.'</td>  
                <td style="text-align: center;font-size: 10px;width: 60px; height: 15px; background-color: lightgray;border: 1px solid black;"> ¿Cuál?</td> 
                <td style="text-align: left;font-size: 10px;width: 217px; height: 15px; border: 1px solid black;">';
               
                if ($salud) 
                    foreach ($salud as $item) 
                        $html2 .= $item->enfermedad .' ';                
            $html2 .= '</td></tr>';
            if($generalesRow['idNivel']<=4){
                    $html2 .='<tr>
                        <td style="text-align: left;font-size: 10px;width: 200px; height: 15px; background-color: lightgray;border: 1px solid black;"> Nombre del Padre o tutor</td>
                        <td style="text-align: left;font-size: 10px;width: 314px; height: 15px; border: 1px solid black;">'.$generalesRow['nombreTutor'].' '.$generalesRow['primerApellidoTutor'].' '.$generalesRow['segundoApellidoTutor'].'</td>  
                        </tr>';   
                    $html2 .='<tr>
                        <td style="text-align: left;font-size: 10px;width: 80px; height: 15px; background-color: lightgray;border: 1px solid black;"> Ocupación</td>
                        <td style="text-align: left;font-size: 10px;width: 153px; height: 15px; border: 1px solid black;">'.$generalesRow['ocupacion'].'</td>  
                        <td style="text-align: center;font-size: 10px;width: 100px; height: 15px; background-color: lightgray;border: 1px solid black;"> Teléfono</td>                      
                        <td style="text-align: left;font-size: 10px;width: 175px; height: 15px; border: 1px solid black;">'.$generalesRow['telefonoTutor'].'</td>
                    </tr>';
                    $html2 .='<tr>
                        <td style="text-align: left;font-size: 10px;width: 200px; height: 15px; background-color: lightgray;border: 1px solid black;"> Dirección del tutor</td>
                        <td style="text-align: left;font-size: 10px;width: 314px; height: 15px; border: 1px solid black;">'.$generalesRow['direccionTutor'].'</td>       
                        </tr>';
            }   
        $html2 .='<tr>
                <td style="text-align: center;font-size: 8px;width: 50px; height: 10px;">Clave</td>
                <td style="text-align: center;font-size: 8px;width: 250px; height: 10px;"></td>  
                <td style="text-align: center;font-size: 8px;width: 100px; height: 10px;">Semestre</td>  
                <td style="text-align: center;font-size: 8px;width: 100px; height: 10px;">Horario</td>  
        </tr>';        
        $html2 .='<tr>
        <td style="text-align: center;font-size: 8px;width: 50px; height: 15px; background-color: lightgray;border: 1px solid black;"></td>
        <td style="text-align: center;font-size: 8px;width: 250px; height: 15px; border: 1px solid black;"></td>  
        <td style="text-align: center;font-size: 8px;width: 100px; height: 15px; border: 1px solid black;"></td>  
        <td style="text-align: center;font-size: 8px;width: 107px; height: 15px; border: 1px solid black;"></td>  
        </tr>';   
        $html2 .='<tr>
                <td style="text-align: left;font-size: 10px;width: 100px; height: 15px; background-color: lightgray;border: 1px solid black;"> CARRERA</td>
                <td style="text-align: left;font-size: 10px;width: 414px; height: 15px; border: 1px solid black;">'.$generalesRow['carrera'].'</td>  
                </tr>';
            $html2 .= '<tr><td style="font-size: 10px;width: 170px; height: 15px; background-color: lightgray;border: 1px solid black;">Número de grupo</td>
                <td style="font-size: 10px;width: 170px; height: 15px; background-color: lightgray;border: 1px solid black;">Fecha de inscripción</td>
                <td style="font-size: 10px;width: 170px; height: 15px; background-color: lightgray;border: 1px solid black;">Fecha de inicio de clases</td>
                </tr>';       
            $html2 .='<tr><td style="font-size: 10px;width: 170px; height: 15px; border: 1px solid black;"></td>  
                <td style="font-size: 10px;width: 170px; height: 15px; border: 1px solid black;"></td>
                <td style="font-size: 10px;width: 170px; height: 15px; border: 1px solid black;"></td>    
                </tr>'; 
            $html2 .= '<tr><td style="font-size: 10px;width: 170px; height: 15px; background-color: lightgray;border: 1px solid black;">Nombre del asesor de R.P.</td>
                <td style="font-size: 8px;width: 170px; height: 15px; background-color: lightgray;border: 1px solid black;">Porque medio se enteró de la Universidad</td>
                <td style="font-size: 10px;width: 170px; height: 15px; background-color: lightgray;border: 1px solid black;">Observaciones</td>
                </tr>';     
            $html2 .='<tr><td style="font-size: 10px;width: 170px; height: 40px; border: 1px solid black;">'.$generalesRow['nombreAsesor'].'</td>  
                <td style="font-size: 10px;width: 170px; height: 40px; border: 1px solid black;">';
            
            $medios = DB::table('detMedio')
                            ->join('medio', 'detMedio.idMedio', '=', 'medio.idMedio')
                            ->select('descripcion as medio')
                            ->where('uid', $generalesRow['uid'])
                            ->get();
            
            if ($medios) 
                foreach ($medios as $item) 
                    $html2 .= $item->medio .'<br>';      

            $html2 .='</td><td style="font-size: 10px;width: 170px; height: 40px; border: 1px solid black;">'.$generalesRow['observaciones'].'</td>     
                </tr>';   
           $html2 .= '</table>';   
           $html2 .= '<br><br>NOTA: No se hacen devoluciones por concepto de inscripción, primera colegiatura y seguro escolar bajo ninguna circunstancia.';
           $html2 .= '<br>IMPORTANTE: El pago de la colegiatura empezará a partir del mes de AGOSTO, fecha indicada de inicio de clases.';
           $html2 .= '<br><br><br><br><br><br><br><br><br><br><br><br>';
           $html2 .= '<table cellpadding="1" style="text-align: center;vertical-align: middle;border-collapse: separate; border-spacing: 3px;">';
           $html2 .='<tr>'.
                      (intval($generalesRow['idNivel'])<=4? 
                       '<td style="text-align: center;font-size: 8px;width: 120px; height: 1px;"><hr style="border: 1px solid black; width: 80%;"></td>
                        <td style="text-align: center;font-size: 8px;width: 120px; height: 2px;"><hr style="border: 1px solid black; width: 80%;"></td>  
                        <td style="text-align: center;font-size: 8px;width: 130px; height: 2px;"><hr style="border: 1px solid black; width: 80%;"></td> 
                        <td style="text-align: center;font-size: 8px;width: 130px; height: 2px;"><hr style="border: 1px solid black; width: 80%;"></td> ':
                       '<td><hr style="border: 1px solid black; width: 90%; margin-left: 60px;"></td>                      
                        <td><hr style="border: 1px solid black; width: 90%; margin-left: 60px;"></td>                        
                        <td><hr style="border: 1px solid black; width: 90%;"></td>'       
                    ).
                    '</tr>';   
           $html2 .='<tr>'.
                     (intval($generalesRow['idNivel'])<=4?
                    '<td style="text-align: center;font-size: 8px;width: 100px; height: 7px;">Control escolar</td>
                     <td style="text-align: center;font-size: 8px;width: 130px; height: 7px;">Relaciones Públicas</td>               
                     <td style="text-align: center;font-size: 8px;width: 140px; height: 7px;">Padre o tutor<br>Nombre y firma</td> 
                     <td style="text-align: center;font-size: 8px;width: 140px; height: 7px;">Alumno</td> ' :
                    '<td style="text-align: center; font-size: 8px; width: 150px; height: 7px; margin-left: 60px;">Control escolar</td>
                        <td style="text-align: center; font-size: 8px; width: 180px; height: 7px; margin-left: 60px;">Relaciones Públicas</td>
                        <td style="text-align: center; font-size: 8px; width: 140px; height: 7px;">Alumno</td> ').                       
                    '</tr>           
                    <tr>
                    <td style="text-align: center;font-size: 8px;width: 150px; height: 10px;"></td>
                    <td style="text-align: center;font-size: 8px;width: 180px; height: 10px;"></td>  
                    <td style="text-align: left;font-size: 8px;width: 200px; height: 10px;">'.
                    (intval($generalesRow['idNivel'])<=4?
                    'Firmamos de leído y aceptado reglamento interno para alumnos de Universidad Alva Edison, sin protesta alguna':
                    'Firmo de leído y aceptado reglamento interno para alumnos de Universidad Alva Edison, sin protesta alguna').
                    '</td>  
                    </tr>   
                    </table>';   
                    Log::info('Nivel :'.$generalesRow['idNivel']);  
           // Escribir la tabla en el PDF
           $pdf->writeHTML($html2, true, false, true, false, '');
           $filePath = storage_path('app/public/solicitudInscripcion.pdf');  // Ruta donde se guardará el archivo
          
           $pdf->Output($filePath, 'F');  // 'F' para guardar el archivo en el servidor
     
           // Ahora puedes verificar si el archivo se ha guardado correctamente en la ruta especificada.
           if (file_exists($filePath)) {
               return response()->json([
                   'status' => 200,  
                   'message' => 'https://reportes.siaweb.com.mx/storage/app/public/solicitudInscripcion.pdf'// Puedes devolver la ruta para fines de depuración
               ]);
           } else {
               return response()->json([
                   'status' => 500,
                   'message' => 'Error al generar el reporte'
               ]);
           }     
     }

    public function convierte(Request $request){

         $validator = Validator::make($request->all(), [
                                            'uid' => 'required|max:255',                            
                                            'idPeriodo' => 'required|max:255',
                                            'idNivel'=> 'required|max:255',
                                            'secuencia'=> 'required|max:255',
                                            'idCarrera' => 'required|max:255',
                                            'idTurno'=> 'required|max:255',
                                            'semestre'=> 'required|max:255',
                                            'uidMatricula'=> 'required|max:255'
        ]);

       
        if ($validator->fails()) {
            $data = [
                'message' => 'Error en la validación de los datos',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
                        Log::info('uid :'.$request->uid);   
                        Log::info('idPeriodo :'.$request->idPeriodo);   
                        Log::info('secuencia :'.$request->secuencia);   
                        Log::info('idCarrera :'.$request->idCarrera);   
                        Log::info('idTurno :'.$request->idTurno);   
                        Log::info('semestre :'.$request->semestre);   
                        Log::info('uidMatricula :'.$request->uidMatricula);   
                        Log::info('idNivel :'.$request->idNivel); 

        $result = DB::select('CALL conviertealumno(?, ?, ?, ?, ?, ?, ?,?)', 
                                                        [$request->uid,$request->idPeriodo, 
                                                        $request->secuencia,$request->idCarrera,$request->idTurno,
                                                        $request->semestre,$request->uidMatricula,$request->idNivel
                                                        ]);
                Log::info('resultado :',$result);   

       
        $data = ['msj' => 'Proceso exitoso','status' => 200];
    
        return response()->json($data, 200);

    }

    public function destroy($uid,$secuencia){
       
        if (!isset($uid)  || !isset($secuencia)) {
            $data = [
                'message' => 'Error en la validación de los datos',                
                'status' => 400
            ];
            return response()->json($data, 400);
        }

        $result = DB::select('CALL borraaspirante(?, ?)', 
                            [$uid,$secuencia]);
        Log::info('resultado :',$result);  
        $data = ['msj' => 'Proceso exitoso','status' => 200];
    
        return response()->json($data, 200);

    }

}
