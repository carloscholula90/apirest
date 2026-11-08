<?php

namespace App\Http\Controllers\Api\nomina;

use App\Http\Controllers\Controller;
use App\Models\nomina\CostoHoraRegimen;
use App\Models\nomina\RegimenFiscalImpuesto;
use App\Models\nomina\RegimenFiscalNomina;
use App\Models\tesoreria\Impuesto;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegimenFiscalNominaController extends Controller
{
    // Incluye cuantos empleados activos tiene asignados cada regimen
    public function index()
    {
        $regimenesFiscales = RegimenFiscalNomina::query()
            ->select('regimenFiscalNomina.*')
            ->selectSub(function ($query) {
                $query->from('empleado')
                    ->selectRaw('COUNT(DISTINCT empleado.uid)')
                    ->whereColumn('empleado.idRegimenFiscalNomina', 'regimenFiscalNomina.idRegimenFiscal')
                    ->whereNull('empleado.fechabaja');
            }, 'empleados')
            ->orderBy('descripcion')
            ->get();

        return $this->returnData('regimenesFiscales', $regimenesFiscales, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $descripcion = mb_strtoupper(trim($request->descripcion), 'UTF-8');

        if (RegimenFiscalNomina::where('descripcion', $descripcion)->exists()) {
            return $this->returnEstatus('Ya existe el regimen fiscal ' . $descripcion, 400, null);
        }

        $regimenFiscal = RegimenFiscalNomina::create([
            'descripcion' => $descripcion,
            'activo' => 1,
        ]);

        return $this->returnData('regimenFiscal', $regimenFiscal, 200);
    }

    public function costos($idRegimenFiscal)
    {
        if (!RegimenFiscalNomina::find($idRegimenFiscal)) {
            return $this->returnEstatus('Regimen fiscal no encontrado', 404, null);
        }

        $costosHora = CostoHoraRegimen::where('idRegimenFiscal', $idRegimenFiscal)
            ->orderByDesc('vigenciaInicio')
            ->get();

        return $this->returnData('costosHora', $costosHora, 200);
    }

    // Un costo nuevo cierra la vigencia del costo vigente el dia anterior a su inicio
    public function storeCosto(Request $request, $idRegimenFiscal)
    {
        if (!RegimenFiscalNomina::find($idRegimenFiscal)) {
            return $this->returnEstatus('Regimen fiscal no encontrado', 404, null);
        }

        $validator = Validator::make($request->all(), [
            'costoHora' => 'required|numeric|gt:0',
            'vigenciaInicio' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $vigente = CostoHoraRegimen::where('idRegimenFiscal', $idRegimenFiscal)
            ->whereNull('vigenciaFin')
            ->first();

        if ($vigente && $request->vigenciaInicio <= $vigente->vigenciaInicio) {
            return $this->returnEstatus('La nueva vigencia debe iniciar despues del ' . date('d/m/Y', strtotime($vigente->vigenciaInicio)), 400, null);
        }

        $costoHora = DB::transaction(function () use ($request, $idRegimenFiscal, $vigente) {
            if ($vigente) {
                $vigente->vigenciaFin = date('Y-m-d', strtotime($request->vigenciaInicio . ' -1 day'));
                $vigente->save();
            }

            return CostoHoraRegimen::create([
                'idRegimenFiscal' => $idRegimenFiscal,
                'costoHora' => $request->costoHora,
                'vigenciaInicio' => $request->vigenciaInicio,
                'vigenciaFin' => null,
            ]);
        });

        return $this->returnData('costoHora', $costoHora, 200);
    }

    public function impuestos($idRegimenFiscal)
    {
        if (!RegimenFiscalNomina::find($idRegimenFiscal)) {
            return $this->returnEstatus('Regimen fiscal no encontrado', 404, null);
        }

        $impuestos = DB::table('regimenFiscalImpuesto')
            ->join('impuestos', 'impuestos.idImpuesto', '=', 'regimenFiscalImpuesto.idImpuesto')
            ->where('regimenFiscalImpuesto.idRegimenFiscal', $idRegimenFiscal)
            ->where('regimenFiscalImpuesto.activo', 1)
            ->select(
                'regimenFiscalImpuesto.idRegimenFiscalImpuesto',
                'impuestos.idImpuesto',
                'impuestos.descripcion',
                'impuestos.porcentaje',
                'impuestos.esRetencion'
            )
            ->orderBy('impuestos.esRetencion')
            ->orderBy('impuestos.descripcion')
            ->get();

        return $this->returnData('impuestos', $impuestos, 200);
    }

    public function storeImpuesto(Request $request, $idRegimenFiscal)
    {
        if (!RegimenFiscalNomina::find($idRegimenFiscal)) {
            return $this->returnEstatus('Regimen fiscal no encontrado', 404, null);
        }

        $validator = Validator::make($request->all(), [
            'idImpuesto' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        if (!Impuesto::find($request->idImpuesto)) {
            return $this->returnEstatus('Impuesto no encontrado', 404, null);
        }

        $relacion = RegimenFiscalImpuesto::where('idRegimenFiscal', $idRegimenFiscal)
            ->where('idImpuesto', $request->idImpuesto)
            ->first();

        if ($relacion && $relacion->activo) {
            return $this->returnEstatus('El impuesto ya esta asignado a este regimen', 400, null);
        }

        // Si se habia quitado antes y ya tenia pagos, se reactiva la misma relacion
        if ($relacion) {
            $relacion->activo = 1;
            $relacion->save();
        } else {
            $relacion = RegimenFiscalImpuesto::create([
                'idRegimenFiscal' => $idRegimenFiscal,
                'idImpuesto' => $request->idImpuesto,
                'activo' => 1,
                'fechaCreacion' => now(),
            ]);
        }

        return $this->returnData('regimenFiscalImpuesto', $relacion, 200);
    }

    // Si la relacion ya se uso en pagos procesados solo se desactiva, para no perder el historico
    public function destroyImpuesto($idRegimenFiscal, $idImpuesto)
    {
        $relacion = RegimenFiscalImpuesto::where('idRegimenFiscal', $idRegimenFiscal)
            ->where('idImpuesto', $idImpuesto)
            ->where('activo', 1)
            ->first();

        if (!$relacion) {
            return $this->returnEstatus('El impuesto no esta asignado a este regimen', 404, null);
        }

        $usado = DB::table('detallePago')
            ->where('idRegimenFiscalImpuesto', $relacion->idRegimenFiscalImpuesto)
            ->exists();

        try {
            if ($usado) {
                $relacion->activo = 0;
                $relacion->save();
            } else {
                $relacion->delete();
            }
        } catch (QueryException $e) {
            return $this->returnEstatus('Error al quitar el impuesto del regimen', 400, null);
        }

        return $this->returnEstatus('Impuesto quitado del regimen', 200, null);
    }

    // Asigna el regimen a todos los registros activos del empleado
    public function asignarEmpleado($idRegimenFiscal, $uid)
    {
        if (!RegimenFiscalNomina::find($idRegimenFiscal)) {
            return $this->returnEstatus('Regimen fiscal no encontrado', 404, null);
        }

        $actualizados = DB::table('empleado')
            ->where('uid', $uid)
            ->whereNull('fechabaja')
            ->update(['idRegimenFiscalNomina' => $idRegimenFiscal]);

        if (!$actualizados && !DB::table('empleado')->where('uid', $uid)->whereNull('fechabaja')->exists()) {
            return $this->returnEstatus('Empleado activo no encontrado', 404, null);
        }

        return $this->returnEstatus('Regimen fiscal asignado al empleado', 200, null);
    }

    public function empleadosSinRegimen()
    {
        $empleados = DB::table('empleado')
            ->join('persona', 'persona.uid', '=', 'empleado.uid')
            ->whereNull('empleado.fechabaja')
            ->whereNull('empleado.idRegimenFiscalNomina')
            ->select('persona.uid', 'persona.nombre', 'persona.primerApellido', 'persona.segundoApellido')
            ->distinct()
            ->orderBy('persona.primerApellido')
            ->get();

        return $this->returnData('empleados', $empleados, 200);
    }
}
