<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yasumi\Yasumi;
use App\Models\general\DiasFestivos;

class DiasFestivosController extends Controller
{
    public function getHolidays($year)
{
    $officialNames = [
                    'Año Nuevo',
                    'Día de la Constitución',
                    'Natalicio de Benito Juárez',
                    'Día del Trabajo',
                    'Día de la Independencia',
                    'Día de la Revolución',
                    'Día de los Muertos',
                    'Navidad',
                ];

    try {
        // Forzar idioma a español
        $holidays = \Yasumi\Yasumi::create('Mexico', (int)$year, 'es_ES');
         $data = [];
        foreach ($holidays as $holiday) {
            if (in_array($holiday->getName(), $officialNames)) {
                $diasFestivo = DiasFestivos::create([
                        'fechaFestivo' => $holiday->format('Y-m-d'),
                        'descripcion' => strtoupper(trim($holiday->getName()))
                ]);
                $data[] = [
                    'date' => $holiday->format('Y-m-d'),
                    'name' => $holiday->getName(),
                ];
            }
        }

        return response()->json([
            'year' => $year,
            'country' => 'Mexico',
            'holidays' => $data
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error al obtener los días festivos.',
            'message' => $e->getMessage()
        ], 500);
    }
}

 public function store(Request $request){
            
            $validator = Validator::make($request->all(), [
                            'descripcion' => 'required|max:255',
                            'fechaFestivo'=> 'required|date'
                        ]);
            
            if ($validator->fails()) 
                return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

                try{
                $diasFestivo = DiasFestivos::create([
                                'fechaFestivo' => $request->fechaFestivo,
                                'descripcion' => strtoupper(trim($request->descripcion))
                ]);

                } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('La fecha ya se encuentra dado de alta',400,null);
            return $this->returnEstatus('Error al insertar la fecha',400,null);
            }

            if (!$diasFestivo) 
                return $this->returnEstatus('Error al crear el dia festivo',500,null); 
             return $this->returnData('Escolaridades',$diasFestivo,200);

 }

 public function destroy($id)
    {
        $diasfestivos = DiasFestivos::find($id);
        if (!$diasfestivos)
            return $this->returnEstatus('Dia festivo no encontrada',404,null);         
          try {
                $diasfestivos->delete();
                return $this->returnEstatus('Dia festivo eliminada',200,null); 
        } catch (QueryException $e) {
        if ($e->getCode() == '23000') {
            return $this->returnEstatus('No se puede eliminar el dia festivo ya esta siendo utilizado',400,null); 
            } 
        }  
    }
   

}
