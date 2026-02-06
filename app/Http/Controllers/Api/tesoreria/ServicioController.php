<?php

namespace App\Http\Controllers\Api\tesoreria;  
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class ServicioController extends Controller
{


public function index($uid, $matricula, $tipoEdoCta)
{
    // Validación básica de parámetros
    if (!is_numeric($uid) || !is_numeric($matricula) || !is_numeric($tipoEdoCta)) {
        abort(400, 'Parámetros inválidos');
    }

    $data = $this->condonacion($uid,$matricula,$tipoEdoCta);

    if($tipoEdoCta == 2 && isset($data))
          $data = DB::table('servicio as s')
            ->join('alumno as al', function ($join) use ($uid, $matricula) {
                $join->where('al.uid', '=', $uid)
                    ->where('al.matricula', '=', $matricula);
            })
            ->join('nivel as niv', 'niv.idNivel', '=', 'al.idNivel')
            ->join('periodo as per', function ($join) {
                $join->on('per.idNivel', '=', 'al.idNivel')
                    ->where('per.activo', '=', 1);
            })
            ->leftJoin('servicioXPeriodo as sxp', function ($join) {
                $join->on('sxp.idNivel', '=', 'al.idNivel')
                    ->on('sxp.idPeriodo', '=', 'per.idPeriodo')
                    ->on('sxp.idServicio', '=', 's.idServicio');
            })
            ->where('s.tipoEdoCta', '=', 2)
            ->select([
                'niv.idNivel',
                'niv.descripcion as nivel',
                's.descripcion as servicio',
                's.efectivo',
                's.tarjeta',
                'per.idPeriodo',
                's.idServicio',
                's.tipoEdoCta',
                DB::raw('IFNULL(sxp.monto, 0) AS monto'),
                DB::raw('IFNULL(s.cargoAutomatico, 0) AS cargoAut'),
            ])
            ->get();
    return $data->first();
}

public function condonacion($uid, $matricula, $tipoEdoCta){
    // Validación básica de parámetros
    if (!is_numeric($uid) || !is_numeric($matricula)|| !is_numeric($tipoEdoCta)) 
        abort(400, 'Parámetros inválidos');
    
    $query = DB::table(DB::raw("(
                SELECT 
                    al.idNivel,
                    niv.descripcion as nivel,
                    s.efectivo,
                    s.tarjeta,
                    per.idPeriodo,
                    s.idServicio,
                    s.tipoEdoCta,
                    cta.uid,
                    al.matricula,
                    cta.parcialidad,
                    cta.secuencia,

                    GROUP_CONCAT(DISTINCT CONCAT(
                        s.descripcion, ' ',
                        CASE 
                            WHEN s.descripcion LIKE '%INSCRIP%' THEN ''
                            ELSE CASE CONVERT(SUBSTRING(cta.referencia, 4), UNSIGNED)
                                WHEN 1 THEN 'ENERO'
                                WHEN 2 THEN 'FEBRERO'
                                WHEN 3 THEN 'MARZO'
                                WHEN 4 THEN 'ABRIL'
                                WHEN 5 THEN 'MAYO'
                                WHEN 6 THEN 'JUNIO'
                                WHEN 7 THEN 'JULIO'
                                WHEN 8 THEN 'AGOSTO'
                                WHEN 9 THEN 'SEPTIEMBRE'
                                WHEN 10 THEN 'OCTUBRE'
                                WHEN 11 THEN 'NOVIEMBRE'
                                WHEN 12 THEN 'DICIEMBRE'
                                ELSE ''
                            END
                        END
                    ) ORDER BY s.descripcion SEPARATOR ' + ') AS servicios,
                     GROUP_CONCAT(DISTINCT CONCAT(
                        s.descripcion, ' ',
                        CASE 
                            WHEN s.descripcion LIKE '%INSCRIP%' THEN ''
                            ELSE CASE CONVERT(SUBSTRING(cta.referencia, 4), UNSIGNED)
                                WHEN 1 THEN 'ENERO'
                                WHEN 2 THEN 'FEBRERO'
                                WHEN 3 THEN 'MARZO'
                                WHEN 4 THEN 'ABRIL'
                                WHEN 5 THEN 'MAYO'
                                WHEN 6 THEN 'JUNIO'
                                WHEN 7 THEN 'JULIO'
                                WHEN 8 THEN 'AGOSTO'
                                WHEN 9 THEN 'SEPTIEMBRE'
                                WHEN 10 THEN 'OCTUBRE'
                                WHEN 11 THEN 'NOVIEMBRE'
                                WHEN 12 THEN 'DICIEMBRE'
                                ELSE ''
                            END
                        END
                    ) ORDER BY s.descripcion SEPARATOR ' + ') AS servicio,

                    SUM(
                        CASE 
                            WHEN cta.tipomovto = 'C' THEN cta.importe
                            WHEN cta.tipomovto = 'A' THEN -cta.importe
                            ELSE 0
                        END
                    ) AS monto,

                    MAX(CASE WHEN cta.tipomovto = 'C' THEN cta.fechaVencimiento END) AS fechaVencimiento,
                    s.cargoAutomatico AS cargoAut

                FROM configuracionTesoreria ct
                INNER JOIN alumno al ON ct.idNivel = al.idNivel
                INNER JOIN periodo per ON per.idNivel = al.idNivel AND per.activo = 1
                INNER JOIN nivel niv ON niv.idNivel = al.idNivel
                INNER JOIN servicio s ON s.tipoEdoCta = 1
                INNER JOIN edocta cta 
                    ON cta.idServicio = s.idServicio
                    AND cta.uid = al.uid
                    AND cta.secuencia = al.secuencia
                    AND cta.idPeriodo = per.idPeriodo
                WHERE al.uid =". $uid.
                " AND al.matricula = ".$matricula.
                " AND s.tipoEdoCta=".$tipoEdoCta.
                " GROUP BY
                    al.idNivel,
                    s.efectivo,
                    s.tarjeta,
                    per.idPeriodo,
                    s.idServicio,
                    s.tipoEdoCta,
                    cta.uid,
                    al.matricula,
                    cta.parcialidad,
                    cta.secuencia,
                    s.cargoAutomatico,
                    niv.descripcion
            ) AS t"))   // 👈 alias obligatorio
            ->where('monto', '>', 0)
            ->orderBy('matricula', 'asc')
            ->orderBy('fechaVencimiento', 'asc')
            ->get();
        return $query;
}

public function store(Request $request){

        $validator = Validator::make($request->all(), [
                    'descripcion' => 'required|max:255'
        ]);

        if ($validator->fails()) 
            return $this->returnEstatus('Error en la validación de los datos',400,$validator->errors()); 

        $maxId = Beca::max('idBeca');  
        $newId = $maxId ? $maxId + 1 : 1; 
        try {
            $becas = Beca::create([
                            'idBeca' => $newId,
                            'descripcion' => strtoupper(trim($request->descripcion)),
                            'aplicaInscripcion' => $request->aplicaInscripcion,
                            'aplicaColegiatura' => $request->aplicaColegiatura,
                            'fechaAlta' => Carbon::now(),
                            'fechaModificacion' => Carbon::now()
            ]);
        } catch (QueryException $e) {
            // Capturamos el error relacionado con las restricciones
            if ($e->getCode() == '23000') 
                // Código de error para restricción violada (por ejemplo, clave foránea)
                return $this->returnEstatus('La Beca ya se encuentra dado de alta',400,null);
                
            return $this->returnEstatus('Error al insertar la Beca',400,null);
        }

        if (!$becas) 
            return $this->returnEstatus('Error al crear la Beca',500,null); 
        return $this->returnData('becas',$becas,200);   
    }


    public function condonar(Request $request){

        $data = $request->validate(['movimientos' => 'required|array']);
        $fecha = Carbon::now('America/Mexico_City')->format('Y-m-d');
        DB::beginTransaction();

        try {

            foreach ($data['movimientos'] as $movimiento) 
            DB::table('edocta')
                ->where('uid', $movimiento['uid'])
                ->where('secuencia', $movimiento['secuencia'])
                ->where('idServicio', $movimiento['idServicio'])
                ->where('consecutivo', $movimiento['consecutivo'])
                ->update([
                    'importe' => DB::raw("importe - {$movimiento['monto']}"),
                    'fechaMovto' => $fecha,
                    'uidcajero' => $movimiento['uidcajero']
                ]);
            DB::commit();
            return $this->returnData('mensaje', 'condonacion exitosa', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->returnEstatus('Error actualizar el registro', 500, $e->getMessage());
        }
    }
    
  }