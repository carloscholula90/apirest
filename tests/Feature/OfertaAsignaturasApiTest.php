<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Corre sobre SQLite en memoria con solo las columnas que usan estos endpoints
class OfertaAsignaturasApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'oferta_test',
            'database.connections.oferta_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('oferta_test');

        Schema::create('grupos', function (Blueprint $table) {
            $table->integer('gruposec')->primary();
            $table->integer('idNivel');
            $table->integer('idPeriodo');
            $table->string('idAsignatura');
            $table->string('grupo');
            $table->integer('idTurno')->nullable();
            $table->integer('uidProfesor')->nullable();
            $table->integer('secuenciaProfesor')->nullable();
            $table->integer('inscritos')->nullable();
            $table->integer('capacidad')->nullable();
            $table->integer('uidSecretario')->nullable();
            $table->integer('uidPresidente')->nullable();
            $table->integer('uidSupervisor')->nullable();
            $table->date('fechaIni')->nullable();
            $table->string('horaIni')->nullable();
            $table->string('horaFin')->nullable();
            $table->integer('idFormato')->nullable();
            $table->string('logoSep')->nullable();
            $table->string('logoEscudo')->nullable();
        });
        Schema::create('horarios', function (Blueprint $table) {
            $table->increments('secHorarios');
            $table->integer('grupoSec');
            $table->dateTime('fechaIni');
            $table->dateTime('fechaFin');
            $table->tinyInteger('day');
            $table->tinyInteger('impartido')->nullable();
        });
        Schema::create('formato', function (Blueprint $table) {
            $table->integer('idFormato');
            $table->string('nombre');
            $table->string('ruta')->nullable();
            $table->integer('default')->nullable();
        });
        Schema::create('diasFestivos', function (Blueprint $table) {
            $table->date('fechaFestivo');
            $table->string('descripcion')->nullable();
        });
        Schema::create('periodo', function (Blueprint $table) {
            $table->integer('idNivel');
            $table->integer('idPeriodo');
            $table->date('fechaInicio')->nullable();
            $table->date('fechaTermino')->nullable();
        });
        Schema::create('duracionSesion', function (Blueprint $table) {
            $table->integer('idNivel');
            $table->integer('idTurno');
            $table->integer('duracion');
        });
        Schema::create('configuracion', function (Blueprint $table) {
            $table->integer('id_campo');
            $table->string('valor')->nullable();
            $table->string('descripcion')->nullable();
        });
        Schema::create('persona', function (Blueprint $table) {
            $table->integer('uid');
            $table->string('nombre');
            $table->string('primerApellido');
            $table->string('segundoApellido')->nullable();
        });
        Schema::create('asignatura', function (Blueprint $table) {
            $table->string('idAsignatura');
            $table->string('descripcion');
        });
        Schema::create('turno', function (Blueprint $table) {
            $table->integer('idTurno');
            $table->string('descripcion');
        });
        Schema::create('detasignatura', function (Blueprint $table) {
            $table->string('idAsignatura');
            $table->integer('idNivel');
            $table->integer('idCarrera');
        });

        // Dos semanas: del lunes 3 al domingo 16 de agosto de 2026
        DB::table('periodo')->insert(['idNivel' => 5, 'idPeriodo' => 112, 'fechaInicio' => '2026-08-03', 'fechaTermino' => '2026-08-16']);
        DB::table('duracionSesion')->insert([
            ['idNivel' => 5, 'idTurno' => 1, 'duracion' => 50],
            ['idNivel' => 5, 'idTurno' => 2, 'duracion' => 45],
        ]);
        DB::table('configuracion')->insert([
            ['id_campo' => 1, 'valor' => '1', 'descripcion' => 'OTRA CONFIGURACION'],
            ['id_campo' => 3, 'valor' => 'secretario', 'descripcion' => 'SECRETARIA EJEMPLO'],
            ['id_campo' => 4, 'valor' => 'presidente', 'descripcion' => 'PRESIDENTE EJEMPLO'],
            ['id_campo' => 5, 'valor' => 'supervisor', 'descripcion' => 'SUPERVISOR EJEMPLO'],
        ]);
        DB::table('persona')->insert([
            ['uid' => 101, 'nombre' => 'ANA', 'primerApellido' => 'LOPEZ', 'segundoApellido' => 'RUIZ'],
            ['uid' => 103, 'nombre' => 'SARA', 'primerApellido' => 'DIAZ', 'segundoApellido' => null],
            ['uid' => 104, 'nombre' => 'PEDRO', 'primerApellido' => 'SOTO', 'segundoApellido' => 'MORA'],
            ['uid' => 105, 'nombre' => 'LUIS', 'primerApellido' => 'CANO', 'segundoApellido' => 'VEGA'],
        ]);
        DB::table('asignatura')->insert(['idAsignatura' => 'LIAE05.I', 'descripcion' => "INGLES I\r"]);
        DB::table('turno')->insert([
            ['idTurno' => 1, 'descripcion' => "MATUTINO\r"],
            ['idTurno' => 2, 'descripcion' => "VESPERTINO\r"],
        ]);
        DB::table('detasignatura')->insert(['idAsignatura' => 'LIAE05.I', 'idNivel' => 5, 'idCarrera' => 6]);

        $this->grupo(1, ['inscritos' => 28, 'uidProfesor' => 101, 'uidSecretario' => 101, 'uidPresidente' => null, 'uidSupervisor' => null]);
        $this->grupo(2, ['grupo' => '06V3A', 'idTurno' => 2, 'inscritos' => 0]);
        $this->grupo(3, ['grupo' => '04M2A', 'inscritos' => 10]);
    }

    private function grupo($gruposec, array $datos = [])
    {
        DB::table('grupos')->insert(array_merge([
            'gruposec' => $gruposec,
            'idNivel' => 5,
            'idPeriodo' => 112,
            'idAsignatura' => 'LIAE05.I',
            'grupo' => '06M1A',
            'idTurno' => 1,
            'inscritos' => 0,
            'capacidad' => 40,
        ], $datos));
    }

    public function test_oferta_filtra_por_carrera_y_turno_e_indica_estados(): void
    {
        // Dos sesiones del mismo grupo no deben duplicar su renglon
        DB::table('horarios')->insert([
            ['secHorarios' => 1, 'grupoSec' => 1, 'fechaIni' => '2026-08-17 07:00:00', 'fechaFin' => '2026-08-17 08:40:00', 'day' => 2],
            ['secHorarios' => 2, 'grupoSec' => 1, 'fechaIni' => '2026-08-19 07:00:00', 'fechaFin' => '2026-08-19 08:40:00', 'day' => 4],
        ]);

        $respuesta = $this->getJson('/api/grupos/oferta/5/112/6');

        $respuesta->assertOk()->assertJsonCount(2, 'grupos');
        $primero = $respuesta->json('grupos.0');
        $this->assertSame(1, $primero['gruposec']);
        $this->assertSame('INGLES I', $primero['asignatura']);
        $this->assertSame('MATUTINO', $primero['turno']);
        $this->assertSame('1', (string) $primero['semestre']);
        $this->assertSame(50, $primero['duracionSesion']);
        $this->assertSame('LOPEZ RUIZ ANA', $primero['profesor']);
        $this->assertTrue($primero['horarioCapturado']);
        $this->assertFalse($primero['actaCompleta']);

        $this->getJson('/api/grupos/oferta/5/112/6?idTurno=2')
            ->assertOk()->assertJsonCount(1, 'grupos')->assertJsonPath('grupos.0.gruposec', 2);
        $this->getJson('/api/grupos/oferta/5/112/6?semestre=3')
            ->assertOk()->assertJsonCount(1, 'grupos')->assertJsonPath('grupos.0.grupo', '06V3A');
        $this->getJson('/api/grupos/oferta/5/112/6?grupo=06M1A')
            ->assertOk()->assertJsonCount(1, 'grupos');
    }

    public function test_acta_usa_lo_guardado_y_completa_campo_por_campo_desde_configuracion(): void
    {
        $this->getJson('/api/grupos/actas/1')
            ->assertOk()
            ->assertJsonPath('acta.secretario.uid', 101)
            ->assertJsonPath('acta.secretario.predeterminado', false)
            ->assertJsonPath('acta.secretario.nombre', 'LOPEZ RUIZ ANA')
            ->assertJsonPath('acta.presidente.uid', null)
            ->assertJsonPath('acta.presidente.predeterminado', true)
            ->assertJsonPath('acta.presidente.nombre', 'PRESIDENTE EJEMPLO')
            ->assertJsonPath('acta.supervisor.predeterminado', true)
            ->assertJsonPath('acta.supervisor.nombre', 'SUPERVISOR EJEMPLO');

        $this->getJson('/api/grupos/actas/999')->assertNotFound();
    }

    public function test_guardar_acta_conserva_presidente_y_guarda_supervisor_y_logotipos(): void
    {
        $datos = [
            'uidSecretario' => 103,
            'uidPresidente' => 104,
            'uidSupervisor' => 105,
            'logoSep' => 'LOGOSEP1617',
            'logoEscudo' => 'ESCUDO',
            'fechaIni' => '2026-12-14',
            'horaIni' => '09:00',
            'horaFin' => '11:00',
            'idFormato' => 1,
        ];

        $this->postJson('/api/grupos/actas/1', $datos)->assertOk();

        $grupo = DB::table('grupos')->where('gruposec', 1)->first();
        $this->assertSame(104, (int) $grupo->uidPresidente);
        $this->assertSame(105, (int) $grupo->uidSupervisor);
        $this->assertSame('LOGOSEP1617', $grupo->logoSep);

        // Los clientes que aun mandan uidVocal lo guardan como supervisor
        unset($datos['uidSupervisor']);
        $datos['uidVocal'] = 101;
        $this->postJson('/api/grupos/actas/1', $datos)->assertOk();

        $grupo = DB::table('grupos')->where('gruposec', 1)->first();
        $this->assertSame(104, (int) $grupo->uidPresidente);
        $this->assertSame(101, (int) $grupo->uidSupervisor);

        unset($datos['uidVocal']);
        $this->postJson('/api/grupos/actas/1', $datos)->assertStatus(422);
    }

    public function test_acta_sin_logotipos_ni_formato_conserva_lo_guardado(): void
    {
        DB::table('grupos')->where('gruposec', 1)->update(['logoSep' => 'LOGOSEP05', 'idFormato' => 2]);

        $this->postJson('/api/grupos/actas/1', [
            'uidSecretario' => 103,
            'uidPresidente' => 104,
            'uidSupervisor' => 105,
            'fechaIni' => '2026-12-14',
            'horaIni' => '09:00',
            'horaFin' => '11:00',
        ])->assertOk();

        $grupo = DB::table('grupos')->where('gruposec', 1)->first();
        $this->assertSame('LOGOSEP05', $grupo->logoSep);
        $this->assertSame(2, (int) $grupo->idFormato);
        $this->assertSame(105, (int) $grupo->uidSupervisor);
    }

    public function test_catalogo_de_formatos(): void
    {
        $this->getJson('/api/formatos')->assertOk()->assertJsonCount(0, 'formatos');

        DB::table('formato')->insert(['idFormato' => 1, 'nombre' => 'ACTA', 'ruta' => null, 'default' => 1]);

        $this->getJson('/api/formatos')->assertOk()->assertJsonPath('formatos.0.nombre', 'ACTA');
    }

    public function test_duracion_por_nivel_y_turno(): void
    {
        $this->getJson('/api/duracionsesion/5/2')->assertOk()->assertJsonPath('duracion', 45);
        $this->getJson('/api/duracionsesion/5/9')->assertNotFound();
        $this->getJson('/api/duracionsesion')->assertOk()->assertJsonCount(2, 'duraciones');
    }

    public function test_vista_previa_calcula_sin_guardar(): void
    {
        $respuesta = $this->postJson('/api/horarios/vista-previa', [
            'grupoSec' => 1,
            'dias' => [['day' => 2, 'horaIni' => '07:00', 'sesiones' => 2], ['day' => 4, 'horaIni' => '09:00', 'sesiones' => 1]],
        ]);

        $respuesta->assertOk()
            ->assertJsonCount(4, 'sesiones')
            ->assertJsonPath('sesiones.0.fechaIni', '2026-08-03 07:00:00')
            ->assertJsonPath('sesiones.0.fechaFin', '2026-08-03 08:40:00')
            ->assertJsonPath('sesiones.0.day', 2)
            ->assertJsonPath('sesiones.1.fechaIni', '2026-08-05 09:00:00')
            ->assertJsonPath('sesiones.1.fechaFin', '2026-08-05 09:50:00');
        $this->assertSame(0, DB::table('horarios')->count());
    }

    public function test_las_sesiones_omiten_dias_festivos(): void
    {
        DB::table('diasFestivos')->insert([
            ['fechaFestivo' => '2026-08-10', 'descripcion' => 'DENTRO DEL PERIODO'],
            ['fechaFestivo' => '2026-09-16', 'descripcion' => 'FUERA DEL PERIODO'],
        ]);
        $datos = ['grupoSec' => 1, 'dias' => [['day' => 2, 'horaIni' => '07:00', 'sesiones' => 1]]];

        $this->postJson('/api/horarios/vista-previa', $datos)
            ->assertOk()
            ->assertJsonCount(1, 'sesiones')
            ->assertJsonPath('sesiones.0.fechaIni', '2026-08-03 07:00:00');

        $this->postJson('/api/horarios/create', $datos)->assertOk()->assertJsonPath('horario.total', 1);
        $this->assertFalse(DB::table('horarios')->where('fechaIni', 'like', '2026-08-10%')->exists());

        // Si todas las fechas son festivas no se guarda nada
        DB::table('diasFestivos')->insert(['fechaFestivo' => '2026-08-04', 'descripcion' => 'SEGUNDO FESTIVO']);
        DB::table('diasFestivos')->insert(['fechaFestivo' => '2026-08-11', 'descripcion' => 'TERCER FESTIVO']);
        $this->postJson('/api/horarios/create', ['grupoSec' => 2, 'dias' => [['day' => 3, 'horaIni' => '15:00', 'sesiones' => 1]]])
            ->assertStatus(400);
    }

    public function test_horario_usa_la_duracion_del_nivel_y_turno(): void
    {
        // Grupo 2 es vespertino: 45 minutos por sesion
        $this->postJson('/api/horarios/create', ['grupoSec' => 2, 'dias' => [['day' => 'MARTES', 'horaIni' => '15:00', 'sesiones' => 2]]])
            ->assertOk()
            ->assertJsonPath('horario.duracionSesion', 45)
            ->assertJsonPath('horario.total', 2)
            ->assertJsonPath('horario.dias.0.day', 3)
            ->assertJsonPath('horario.dias.0.horaFin', '16:30')
            ->assertJsonPath('horario.dias.0.sesiones', 2);
    }

    public function test_horario_rechaza_datos_invalidos(): void
    {
        $dia = ['day' => 2, 'horaIni' => '07:00', 'sesiones' => 1];

        foreach ([
            ['grupoSec' => 1],
            ['grupoSec' => 1, 'dias' => []],
            ['grupoSec' => 1, 'dias' => [['day' => 2, 'horaIni' => '07:00', 'sesiones' => 0]]],
            ['grupoSec' => 1, 'dias' => [['day' => 2, 'horaIni' => '7am', 'sesiones' => 1]]],
            ['grupoSec' => 1, 'dias' => [['day' => 9, 'horaIni' => '07:00', 'sesiones' => 1]]],
            ['grupoSec' => 1, 'dias' => [$dia, $dia]],
            ['grupoSec' => 1, 'dias' => [['day' => 2, 'horaIni' => '23:30', 'sesiones' => 1]]],
            ['grupoSec' => 999, 'dias' => [$dia]],
            ['dias' => [$dia]],
        ] as $datos) {
            $this->postJson('/api/horarios/create', $datos)->assertStatus(400);
        }

        // Nivel y turno sin duracion configurada
        $this->grupo(4, ['idTurno' => 3]);
        $this->postJson('/api/horarios/create', ['grupoSec' => 4, 'dias' => [$dia]])->assertStatus(400);

        // Periodo sin fechas
        $this->grupo(5, ['idPeriodo' => 200]);
        $this->postJson('/api/horarios/create', ['grupoSec' => 5, 'dias' => [$dia]])->assertStatus(400);

        $this->assertSame(0, DB::table('horarios')->count());
    }

    public function test_actualizar_reemplaza_pendientes_y_conserva_impartidas(): void
    {
        $dias = ['dias' => [['day' => 2, 'horaIni' => '07:00', 'sesiones' => 1]]];

        $this->postJson('/api/horarios/create', ['grupoSec' => 1] + $dias)->assertOk()->assertJsonPath('horario.total', 2);
        $this->postJson('/api/horarios/create', ['grupoSec' => 1] + $dias)->assertStatus(400);

        DB::table('horarios')->where('fechaIni', '2026-08-03 07:00:00')->update(['impartido' => 1]);

        // Pasa a las 09:00; el lunes impartido no cambia y no se duplica su fecha
        $this->putJson('/api/horarios/1', ['dias' => [['day' => 2, 'horaIni' => '09:00', 'sesiones' => 2]]])
            ->assertOk()
            ->assertJsonPath('horario.total', 2)
            ->assertJsonPath('horario.impartidas', 1)
            ->assertJsonPath('horario.dias.0.horaIni', '09:00')
            ->assertJsonPath('horario.dias.0.horaFin', '10:40');

        $this->assertSame(1, DB::table('horarios')->where('fechaIni', '2026-08-03 07:00:00')->where('impartido', 1)->count());
        $this->assertSame(1, DB::table('horarios')->where('fechaIni', '2026-08-10 09:00:00')->count());

        $this->getJson('/api/horarios/1')->assertOk()->assertJsonCount(2, 'horario.sesiones');

        $this->deleteJson('/api/horarios/1')->assertOk();
        $this->assertSame(1, DB::table('horarios')->where('grupoSec', 1)->count());
        $this->assertSame(1, (int) DB::table('horarios')->where('grupoSec', 1)->value('impartido'));

        $this->getJson('/api/horarios/999')->assertNotFound();
        $this->deleteJson('/api/horarios/999')->assertNotFound();
    }

    public function test_eliminar_grupo_se_bloquea_con_sesiones_impartidas(): void
    {
        DB::table('horarios')->insert(['grupoSec' => 2, 'fechaIni' => '2026-08-04 15:00:00', 'fechaFin' => '2026-08-04 15:45:00', 'day' => 3, 'impartido' => 1]);

        $this->deleteJson('/api/grupos/grupo/2')->assertStatus(400);
        $this->assertTrue(DB::table('grupos')->where('gruposec', 2)->exists());
    }

    public function test_capacidad_no_baja_de_inscritos(): void
    {
        $this->putJson('/api/grupos/capacidad/1', ['capacidad' => 27])->assertStatus(400);
        $this->putJson('/api/grupos/capacidad/1', ['capacidad' => 28])->assertOk();
        $this->assertSame(28, (int) DB::table('grupos')->where('gruposec', 1)->value('capacidad'));
        $this->putJson('/api/grupos/capacidad/999', ['capacidad' => 30])->assertNotFound();
    }

    public function test_logotipo_solo_cambia_el_logo_del_grupo(): void
    {
        $this->putJson('/api/grupos/logotipo/1', [])->assertStatus(400);
        $this->putJson('/api/grupos/logotipo/1', ['logoSep' => 'LOGOSEP05'])->assertOk();
        $this->assertSame('LOGOSEP05', DB::table('grupos')->where('gruposec', 1)->value('logoSep'));
        $this->assertNull(DB::table('grupos')->where('gruposec', 2)->value('logoSep'));
    }

    public function test_eliminar_bloquea_con_inscritos_y_borra_el_horario(): void
    {
        $this->deleteJson('/api/grupos/grupo/1')->assertStatus(400);
        $this->assertTrue(DB::table('grupos')->where('gruposec', 1)->exists());

        DB::table('horarios')->insert(['secHorarios' => 1, 'grupoSec' => 2, 'fechaIni' => '2026-08-18 15:00:00', 'fechaFin' => '2026-08-18 15:45:00', 'day' => 3]);
        $this->deleteJson('/api/grupos/grupo/2')->assertOk();
        $this->assertFalse(DB::table('grupos')->where('gruposec', 2)->exists());
        $this->assertFalse(DB::table('horarios')->where('grupoSec', 2)->exists());
        $this->assertTrue(DB::table('grupos')->where('gruposec', 3)->exists());

        $this->deleteJson('/api/grupos/grupo/999')->assertNotFound();
    }

    public function test_ruta_de_asignaturas_de_la_oferta_carga_su_controlador(): void
    {
        $this->getJson('/api/asignaturas/oferta/5/112/6')
            ->assertOk()
            ->assertJsonPath('asignaturas.0.idAsignatura', 'LIAE05.I');
    }
}
