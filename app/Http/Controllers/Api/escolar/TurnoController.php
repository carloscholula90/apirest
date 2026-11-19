<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Api\escolar\Concerns\ManejaHorarios;
use App\Http\Controllers\Controller;
use App\Models\escolar\DetalleTurno;
use App\Models\escolar\Turno;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TurnoController extends Controller{

    use ManejaHorarios;

    public function index(){       
        $turnos = Turno::all();
        return $this->returnData('turnos',$turnos,200);
    }

    /*
     * Turnos con sus días. Con ?idNivel= se usan los días de ese nivel (o los generales sin nivel si el nivel
     * no tiene). Sin nivel, un registro por día con el horario más amplio entre niveles, para pantallas que
     * no distinguen nivel (por ejemplo el horario del profesor).
     */
    public function turnosConDetalle(Request $request)
    {
        $idNivel = $request->query('idNivel');

        $turnos = Turno::with(['detallesTurno' => function ($query) {
            $query->orderByRaw('CAST(diaSemana AS UNSIGNED)');
        }])
            ->orderBy('descripcion')
            ->get()
            ->map(function ($turno) use ($idNivel) {
                $detalles = $idNivel
                    ? DetalleTurno::paraNivel($turno->idTurno, $idNivel)->sortBy(function ($d) { return (int) $this->diaNumero($d->diaSemana); })->values()
                    : $this->unDiaPorRegistro($turno->detallesTurno);
                $horaInicio = $detalles->min('horaInicio');
                $horaFin = $detalles->max('horaFin');
                $horario = $horaInicio && $horaFin
                    ? $this->formatHora($horaInicio) . ' - ' . $this->formatHora($horaFin)
                    : null;

                return [
                    'idTurno' => $turno->idTurno,
                    'descripcion' => trim($turno->descripcion),
                    'letra' => $turno->letra,
                    'parciales' => $turno->parciales,
                    'textoTurno' => $horario
                        ? trim($turno->descripcion) . ' (' . $horario . ')'
                        : trim($turno->descripcion),
                    'horaInicio' => $horaInicio,
                    'horaFin' => $horaFin,
                    'horario' => $horario,
                    'diasTurno' => $detalles->map(function ($detalle) {
                        return [
                            'idDtlTurno' => $detalle->idDtlTurno,
                            'diaSemana' => $detalle->diaSemana,
                            'diaCorto' => $this->diaCorto($detalle->diaSemana),
                            'horaInicio' => $detalle->horaInicio,
                            'horaFin' => $detalle->horaFin,
                            'idNivel' => $detalle->idNivel !== null ? (int) $detalle->idNivel : null,
                        ];
                    })->values(),
                ];
            });

        return $this->returnData('turnos', $turnos, 200);
    }

    // Junta los días repetidos por nivel en uno solo, con la hora de inicio más temprana y la de fin más tarde
    private function unDiaPorRegistro($detalles)
    {
        return $detalles->groupBy(function ($detalle) {
            return $this->diaNumero($detalle->diaSemana) ?: trim($detalle->diaSemana);
        })->map(function ($delDia) {
            if ($delDia->count() === 1) {
                return $delDia->first();
            }

            $dia = clone $delDia->first();
            $dia->horaInicio = $delDia->min('horaInicio');
            $dia->horaFin = $delDia->max('horaFin');
            $dia->idNivel = null;

            return $dia;
        })->values();
    }

    private function formatHora($hora)
    {
        return substr($hora, 0, 5);
    }

    // diaSemana se guarda como numero (1 domingo ... 7 sabado); tambien se aceptan nombres
    private function diaCorto($diaSemana)
    {
        $dia = $this->diaNombre($diaSemana);

        return $dia ? mb_substr($dia, 0, 1, 'UTF-8') : trim($diaSemana);
    }

    public function store(Request $request)
    {
        $errores = $this->validaTurno($request, null, true);

        if ($errores)
            return $this->returnEstatus('Error en la validación de los datos',400,$errores);

        $maxId = Turno::max('idTurno');
        $newId = $maxId ? $maxId + 1 : 1;
        try{
        $turnos = Turno::create([
                        'idTurno' => $newId,
                        'descripcion' => $this->normaliza($request->descripcion),
                        'letra' => $this->normaliza($request->letra),
                        'parciales' => (int) $request->parciales
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

        $errores = $this->validaTurno($request, $Turno, true);

        if ($errores)
            return $this->returnEstatus('Error en la validación de los datos',400,$errores);

        // La clave (idTurno) no se cambia: la usan dtlTurno, defaultTurno y grupos
        $Turno->descripcion = $this->normaliza($request->descripcion);
        $Turno->letra = $this->normaliza($request->letra);
        $Turno->parciales = (int) $request->parciales;
        $Turno->save();
        return $this->returnData('Turno',$Turno,200);
    }

    public function updatePartial(Request $request, $idTurno){

        $Turno = Turno::find($idTurno);
        
        if (!$Turno) 
            return $this->returnEstatus('Turno no encontrado',404,null);             

        // Solo se validan y cambian los campos que llegan
        $errores = $this->validaTurno($request, $Turno, false);

        if ($errores)
            return $this->returnEstatus('Error en la validación de los datos',400,$errores);

        if ($request->has('descripcion'))
            $Turno->descripcion = $this->normaliza($request->descripcion);

        if ($request->has('letra'))
            $Turno->letra = $this->normaliza($request->letra);

        if ($request->has('parciales'))
            $Turno->parciales = (int) $request->parciales;

        $Turno->save();
        return $this->returnEstatus('Turno actualizado',200,null);
    }

    /*
     * Reglas del turno: descripción (máx. 20, el tamaño de la columna) y letra (una letra) obligatorias y sin
     * repetirse con otro turno, sin importar mayúsculas, espacios ni el \r que traen algunos registros;
     * parciales entero mayor a 0. Con $todos en false (PATCH) solo se revisan los campos que llegan.
     * $actual es el turno que se edita, para no compararlo consigo mismo. Regresa los errores por campo.
     */
    private function validaTurno(Request $request, $actual, $todos)
    {
        $requerido = $todos ? 'required' : 'sometimes|required';

        $validator = Validator::make($request->all(), [
            'descripcion' => $requerido.'|string|max:20',
            'letra' => $requerido.'|string|size:1|alpha',
            'parciales' => $requerido.'|integer|min:1',
        ], [
            'descripcion.required' => 'La descripción del turno es obligatoria.',
            'descripcion.max' => 'La descripción del turno no puede pasar de 20 caracteres.',
            'letra.required' => 'La letra del turno es obligatoria.',
            'letra.size' => 'La letra del turno debe ser una sola letra.',
            'letra.alpha' => 'La letra del turno debe ser una letra.',
            'parciales.required' => 'El número de parciales es obligatorio.',
            'parciales.integer' => 'El número de parciales debe ser un número entero.',
            'parciales.min' => 'El número de parciales debe ser mayor a 0.',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        $errores = [];

        foreach (['descripcion' => 'descripción', 'letra' => 'letra'] as $campo => $nombre) {
            if (!$request->has($campo)) {
                continue;
            }

            $repetido = $this->turnoCon($campo, $request->input($campo), $actual ? $actual->idTurno : null);

            if ($repetido) {
                $errores[$campo] = ['Ya existe un turno con la '.$nombre.' '.$this->normaliza($request->input($campo))
                    .' ('.$this->normaliza($repetido->descripcion).', clave '.$repetido->idTurno.').'];
            }
        }

        return $errores;
    }

    // Otro turno con el mismo valor en el campo, comparando sin mayúsculas, espacios ni \r
    private function turnoCon($campo, $valor, $excluirIdTurno)
    {
        return Turno::whereRaw('UPPER(TRIM(REPLACE('.$campo.', CHAR(13), \'\'))) = ?', [$this->normaliza($valor)])
            ->when($excluirIdTurno !== null, function ($query) use ($excluirIdTurno) {
                $query->where('idTurno', '<>', $excluirIdTurno);
            })
            ->first();
    }

    // Como se guarda: sin espacios ni \r a los lados y en mayúsculas
    private function normaliza($valor)
    {
        return mb_strtoupper(trim(str_replace("\r", '', (string) $valor)), 'UTF-8');
    }

    public function generaReporte(){
        return $this->imprimeCtl('turno',' turnos ',['CLAVE', 'DESCRIPCIÓN','LETRA','PARCIALES'],[100,200,100,100],'descripcion');
     } 
 
     public function exportaExcel() {
        return $this->exportaXLS('turno','idTurno',['CLAVE', 'DESCRIPCIÓN','LETRA','PARCIALES'],'descripcion');     
    } 
}
