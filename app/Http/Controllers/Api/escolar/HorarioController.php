<?php

namespace App\Http\Controllers\Api\escolar;  
use App\Http\Controllers\Controller;
use App\Models\escolar\Horario;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    public function index()
    {
        $horarios = Horario::all();
        return $this->returnData('horarios', $horarios, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grupoSec' => 'required|integer|unique:horarios,grupoSec'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        try{
            $horario = Horario::create([
                'grupoSec' => $request->grupoSec,

                'horalIni' => $request->horalIni,
                'horalFin' => $request->horalFin,
                'horamIni' => $request->horamIni,
                'horamFin' => $request->horamFin,
                'horammIni' => $request->horammIni,
                'horammFin' => $request->horammFin,
                'horajIni' => $request->horajIni,
                'horajFin' => $request->horajFin,
                'horavIni' => $request->horavIni,
                'horavFin' => $request->horavFin,
                'horasIni' => $request->horasIni,
                'horasFin' => $request->horasFin,
                'horadIni' => $request->horadIni,
                'horadFin' => $request->horadFin,
            ]);
        } catch (QueryException $e) {

            if ($e->getCode() == '23000') 
                return $this->returnEstatus('El horario ya existe',400,null);

            return $this->returnEstatus('Error al insertar el horario',400,null);
        }

        if (!$horario) 
            return $this->returnEstatus('Error al crear el horario',500,null); 

        $horario = Horario::findOrFail($request->grupoSec);        
        return $this->returnData('horarios',$horario,200);
    }

    public function show($id)
    {
        $horario = Horario::find($id);

        if (!$horario) 
            return $this->returnEstatus('Horario no encontrado',404,null); 

        return $this->returnData('horarios',$horario,200);
    }

    public function destroy($id)
    {
        $horario = Horario::find($id);

        if (!$horario)
            return $this->returnEstatus('Horario no encontrado',404,null); 

        try {
            $horario->delete();
            return $this->returnEstatus('Horario eliminado',200,null);  

        } catch (QueryException $e) {

            if ($e->getCode() == '23000') 
                return $this->returnEstatus('No se puede eliminar el horario, está siendo utilizado',400,null); 
        }
    }

    public function update(Request $request, $id)
    {
        $horario = Horario::find($id);

        if (!$horario)  
            return $this->returnEstatus('Horario no encontrado',404,null);

        $validator = Validator::make($request->all(), [
            'grupoSec' => 'required|integer'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $horario->grupoSec = $id;

        $horario->horalIni = $request->horalIni;
        $horario->horalFin = $request->horalFin;
        $horario->horamIni = $request->horamIni;
        $horario->horamFin = $request->horamFin;
        $horario->horammIni = $request->horammIni;
        $horario->horammFin = $request->horammFin;
        $horario->horajIni = $request->horajIni;
        $horario->horajFin = $request->horajFin;
        $horario->horavIni = $request->horavIni;
        $horario->horavFin = $request->horavFin;
        $horario->horasIni = $request->horasIni;
        $horario->horasFin = $request->horasFin;
        $horario->horadIni = $request->horadIni;
        $horario->horadFin = $request->horadFin;

        $horario->save();

        return $this->returnEstatus('Horario actualizado',200,null); 
    }

    // Reporte
    public function generaReporte()
    {
        return $this->imprimeCtl('horario','horarios');
    } 

    public function exportaExcel()
    {  
        return $this->exportaXLS('horario','grupoSec', [
            'GRUPO',
            'LUNES INI','LUNES FIN',
            'MARTES INI','MARTES FIN',
            'MIÉRCOLES INI','MIÉRCOLES FIN',
            'JUEVES INI','JUEVES FIN',
            'VIERNES INI','VIERNES FIN',
            'SÁBADO INI','SÁBADO FIN',
            'DOMINGO INI','DOMINGO FIN'
        ]);     
    }
}