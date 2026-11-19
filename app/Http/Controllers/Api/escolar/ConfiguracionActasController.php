<?php

namespace App\Http\Controllers\Api\escolar;

use App\Http\Controllers\Controller;
use App\Models\escolar\ConfiguracionActas;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/*
 * Firmantes predeterminados del acta (secretario y supervisor) por nivel. El presidente predeterminado es el
 * profesor de la asignatura, así que la columna idPresidente de la tabla ya no se usa.
 * Oferta de asignaturas los usa para cada firmante que el acta no tenga guardado.
 * La tabla no tiene llave: se trabaja por idNivel y, si hubiera filas repetidas, se actualizan todas.
 */
class ConfiguracionActasController extends Controller
{
    // Campo de la tabla => nombre del firmante en la respuesta
    private const FIRMANTES = [
        'idSecretario' => 'secretario',
        'idSupervisor' => 'supervisor',
    ];

    // Todos los niveles, tengan o no configuración
    public function index()
    {
        $niveles = DB::table('nivel')->orderBy('idNivel')->get(['idNivel', 'descripcion', 'activo']);
        $filas = ConfiguracionActas::whereIn('idNivel', $niveles->pluck('idNivel'))->get()->keyBy('idNivel');
        $nombres = $this->nombresPersonas($filas);

        $configuracion = $niveles->map(function ($nivel) use ($filas, $nombres) {
            return $this->formato($nivel, $filas->get($nivel->idNivel), $nombres);
        })->values();

        return $this->returnData('configuracionActas', $configuracion, 200);
    }

    public function show($idNivel)
    {
        $nivel = $this->buscaNivel($idNivel);

        if (!$nivel) {
            return $this->returnEstatus('Nivel no encontrado', 404, null);
        }

        $fila = ConfiguracionActas::where('idNivel', $idNivel)->first();

        return $this->returnData('configuracionActas', $this->formato($nivel, $fila, $this->nombresPersonas(collect([$fila])->filter())), 200);
    }

    // Crea o actualiza la fila del nivel; solo cambia los firmantes que llegan (null los quita)
    public function update(Request $request, $idNivel)
    {
        $nivel = $this->buscaNivel($idNivel);

        if (!$nivel) {
            return $this->returnEstatus('Nivel no encontrado', 404, null);
        }

        $reglas = $mensajes = [];

        foreach (self::FIRMANTES as $campo => $rol) {
            $reglas[$campo] = 'nullable|integer|exists:persona,uid';
            $mensajes[$campo.'.exists'] = 'El '.$rol.' no existe en el catálogo de personas.';
            $mensajes[$campo.'.integer'] = 'Elige al '.$rol.' del catálogo de personas.';
        }

        $validator = Validator::make($request->all(), $reglas, $mensajes);

        if ($validator->fails()) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $validator->errors());
        }

        $datos = $request->only(array_keys(self::FIRMANTES));

        if (!$datos) {
            return $this->returnEstatus('Indica al menos un firmante: idSecretario o idSupervisor', 400, null);
        }

        // El secretario y el supervisor deben ser personas distintas; se revisa como quedaría la fila completa
        $actual = DB::table('configuracionActas')->where('idNivel', $idNivel)->first();
        $final = array_merge(array_fill_keys(array_keys(self::FIRMANTES), null), $actual ? (array) $actual : [], $datos);
        $vistos = $repetidos = [];

        foreach (self::FIRMANTES as $campo => $rol) {
            $uid = $final[$campo] ? (int) $final[$campo] : null;
            if ($uid && isset($vistos[$uid])) {
                $repetidos[$campo] = ['El '.$rol.' no puede ser la misma persona que el '.$vistos[$uid].'.'];
            }
            if ($uid) $vistos[$uid] = $vistos[$uid] ?? $rol;
        }

        if ($repetidos) {
            return $this->returnEstatus('Error en la validacion de los datos', 400, $repetidos);
        }

        try {
            $consulta = DB::table('configuracionActas')->where('idNivel', $idNivel);

            if ($consulta->exists()) {
                $consulta->update($datos);
            } else {
                DB::table('configuracionActas')->insert(array_merge(
                    ['idNivel' => (int) $idNivel, 'idSecretario' => null, 'idSupervisor' => null],
                    $datos
                ));
            }
        } catch (QueryException $e) {
            return $this->returnEstatus('Error al guardar la configuracion de actas', 500, null);
        }

        return $this->show($idNivel);
    }

    public function destroy($idNivel)
    {
        if (!$this->buscaNivel($idNivel)) {
            return $this->returnEstatus('Nivel no encontrado', 404, null);
        }

        $borradas = DB::table('configuracionActas')->where('idNivel', $idNivel)->delete();

        if (!$borradas) {
            return $this->returnEstatus('El nivel no tiene configuracion de actas', 404, null);
        }

        return $this->returnEstatus('Configuracion de actas eliminada', 200, null);
    }

    private function buscaNivel($idNivel)
    {
        return DB::table('nivel')->where('idNivel', $idNivel)->first(['idNivel', 'descripcion', 'activo']);
    }

    private function formato($nivel, $fila, array $nombres)
    {
        $resultado = [
            'idNivel' => (int) $nivel->idNivel,
            'nivel' => trim($nivel->descripcion),
            'activo' => (int) $nivel->activo,
        ];

        foreach (self::FIRMANTES as $campo => $rol) {
            $uid = $fila && $fila->$campo ? (int) $fila->$campo : null;
            // Un uid que ya no existe en persona se reporta sin nombre para poder corregirlo
            $resultado[$rol] = ['uid' => $uid, 'nombre' => $uid ? ($nombres[$uid] ?? null) : null];
        }

        return $resultado;
    }

    // Nombre completo (apellidos primero) de los firmantes de las filas, indexado por uid
    private function nombresPersonas($filas)
    {
        $uids = [];

        foreach ($filas as $fila) {
            foreach (array_keys(self::FIRMANTES) as $campo) {
                if ($fila->$campo) $uids[] = $fila->$campo;
            }
        }

        if (!$uids) {
            return [];
        }

        return DB::table('persona')
            ->whereIn('uid', array_unique($uids))
            ->get(['uid', 'primerApellido', 'segundoApellido', 'nombre'])
            ->mapWithKeys(function ($persona) {
                $partes = [$persona->primerApellido, $persona->segundoApellido, $persona->nombre];

                return [$persona->uid => trim(implode(' ', array_filter(array_map('trim', $partes))))];
            })
            ->all();
    }
}
