<?php

namespace App\Http\Controllers\Api\general;  
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yasumi\Yasumi;

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
                $data[] = [
                    'date' => $holiday->format('Y-m-d'),
                    'name' => $holiday->getName(),
                ];
            }

      /*  $data = [];
        foreach ($holidays as $holiday) {
            if (in_array($holiday->getName(), $officialNames)) {
                $data[] = [
                    'date' => $holiday->format('Y-m-d'),
                    'name' => $holiday->getName(),
                ];
            }
        }*/

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

}
