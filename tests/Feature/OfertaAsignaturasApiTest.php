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
        Schema::create('configuracionActas', function (Blueprint $table) {
            $table->integer('idNivel')->nullable();
            $table->integer('idSecretario')->nullable();
            $table->integer('idPresidente')->nullable();
            $table->integer('idSupervisor')->nullable();
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
        Schema::create('defaultTurno', function (Blueprint $table) {
            $table->integer('idTurno');
            $table->time('horaInicio');
            $table->integer('idTipoBloque');
            $table->time('horaFin');
            $table->integer('activo');
        });
        Schema::create('tipoBloque', function (Blueprint $table) {
            $table->integer('idTipoBloque');
            $table->string('descripcion');
            $table->string('color')->nullable();
            $table->integer('activo');
        });
        Schema::create('dtlTurno', function (Blueprint $table) {
            $table->increments('idDtlTurno');
            $table->integer('idTurno');
            $table->string('diaSemana');
            $table->time('horaInicio');
            $table->time('horaFin');
            $table->integer('idNivel')->nullable();
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
        // Licenciatura (5) con sus firmantes; maestria (6) con un supervisor que no existe en persona
        DB::table('configuracionActas')->insert([
            ['idNivel' => 5, 'idSecretario' => 103, 'idPresidente' => 104, 'idSupervisor' => 105],
            ['idNivel' => 6, 'idSecretario' => 104, 'idPresidente' => null, 'idSupervisor' => 999],
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
        // Matutino lunes a viernes 07:00-14:00 (dias como numero); vespertino lunes a viernes 14:00-21:00
        foreach ([2, 3, 4, 5, 6] as $dia) {
            DB::table('dtlTurno')->insert([
                ['idTurno' => 1, 'diaSemana' => (string) $dia, 'horaInicio' => '07:00:00', 'horaFin' => '14:00:00'],
                ['idTurno' => 2, 'diaSemana' => (string) $dia, 'horaInicio' => '14:00:00', 'horaFin' => '21:00:00'],
            ]);
        }
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

    public function test_acta_usa_lo_guardado_y_completa_campo_por_campo_desde_configuracion_actas(): void
    {
        // Grupo 1 (licenciatura, profesor 101) tiene guardado solo el secretario. El presidente predeterminado
        // es el profesor, no el idPresidente de configuracionActas (104)
        $this->getJson('/api/grupos/actas/1')
            ->assertOk()
            ->assertJsonPath('acta.secretario.uid', 101)
            ->assertJsonPath('acta.secretario.predeterminado', false)
            ->assertJsonPath('acta.secretario.nombre', 'LOPEZ RUIZ ANA')
            ->assertJsonPath('acta.presidente.uid', 101)
            ->assertJsonPath('acta.presidente.predeterminado', true)
            ->assertJsonPath('acta.presidente.nombre', 'LOPEZ RUIZ ANA')
            ->assertJsonPath('acta.supervisor.uid', 105)
            ->assertJsonPath('acta.supervisor.predeterminado', true)
            ->assertJsonPath('acta.supervisor.nombre', 'CANO VEGA LUIS');

        // Con presidente guardado se respeta aunque el profesor sea otro
        DB::table('grupos')->where('gruposec', 1)->update(['uidPresidente' => 104]);
        $this->getJson('/api/grupos/actas/1')
            ->assertJsonPath('acta.presidente.uid', 104)
            ->assertJsonPath('acta.presidente.predeterminado', false);

        // Otro nivel usa su propia fila; sin profesor no hay presidente predeterminado;
        // vacio o un uid que no existe en persona no da predeterminado
        $this->grupo(8, ['idNivel' => 6, 'grupo' => '03M1A']);
        $this->getJson('/api/grupos/actas/8')
            ->assertOk()
            ->assertJsonPath('acta.secretario.uid', 104)
            ->assertJsonPath('acta.secretario.predeterminado', true)
            ->assertJsonPath('acta.presidente.uid', null)
            ->assertJsonPath('acta.presidente.predeterminado', false)
            ->assertJsonPath('acta.supervisor.uid', null)
            ->assertJsonPath('acta.supervisor.predeterminado', false);

        // Nivel sin fila en configuracionActas: sin predeterminados
        $this->grupo(9, ['idNivel' => 4, 'grupo' => '01M1A']);
        $this->getJson('/api/grupos/actas/9')
            ->assertOk()
            ->assertJsonPath('acta.secretario.uid', null)
            ->assertJsonPath('acta.secretario.predeterminado', false);

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
            'fechaIni' => '2026-08-14',
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
        // 106 no es el profesor (101) ni otro firmante
        DB::table('persona')->insert(['uid' => 106, 'nombre' => 'MARTA', 'primerApellido' => 'RIOS', 'segundoApellido' => null]);
        $datos['uidVocal'] = 106;
        $this->postJson('/api/grupos/actas/1', $datos)->assertOk();

        $grupo = DB::table('grupos')->where('gruposec', 1)->first();
        $this->assertSame(104, (int) $grupo->uidPresidente);
        $this->assertSame(106, (int) $grupo->uidSupervisor);

        unset($datos['uidVocal']);
        // Sin supervisor en la petición se conserva el guardado (ya no es obligatorio)
        $this->postJson('/api/grupos/actas/1', $datos)->assertOk();
        $this->assertSame(106, (int) DB::table('grupos')->where('gruposec', 1)->value('uidSupervisor'));
    }

    public function test_acta_solo_exige_el_profesor(): void
    {
        // Grupo 1 tiene profesor (101): se guarda aunque falten firmantes, fecha y horas; null vacía lo que había
        $this->postJson('/api/grupos/actas/1', [
            'uidSecretario' => null, 'uidPresidente' => null, 'uidSupervisor' => null,
            'fechaIni' => null, 'horaIni' => null, 'horaFin' => null,
        ])->assertOk();
        $grupo = DB::table('grupos')->where('gruposec', 1)->first();
        $this->assertNull($grupo->uidSecretario);
        $this->assertNull($grupo->fechaIni);

        // Solo algunos campos: los demás se conservan
        $this->postJson('/api/grupos/actas/1', ['uidPresidente' => 104, 'horaIni' => '09:00'])->assertOk();
        $this->postJson('/api/grupos/actas/1', ['uidSecretario' => 103])->assertOk();
        $grupo = DB::table('grupos')->where('gruposec', 1)->first();
        $this->assertSame([103, 104, '09:00'], [(int) $grupo->uidSecretario, (int) $grupo->uidPresidente, substr($grupo->horaIni, 0, 5)]);

        // Lo que llega se sigue validando contra lo guardado: hora de fin antes que la de inicio guardada, firmante repetido
        $this->postJson('/api/grupos/actas/1', ['horaFin' => '08:00'])->assertStatus(422)->assertJsonValidationErrors(['horaFin']);
        $this->postJson('/api/grupos/actas/1', ['uidSupervisor' => 104])->assertStatus(422)
            ->assertJsonPath('errors.uidSupervisor.0', 'El supervisor no puede ser la misma persona que el presidente.');

        // Sin profesor no se puede guardar
        $this->postJson('/api/grupos/actas/3', ['uidSecretario' => 103])->assertStatus(422)
            ->assertJsonPath('errors.uidProfesor.0', 'Asigna el profesor de la asignatura antes de guardar el acta.');
        $this->assertNull(DB::table('grupos')->where('gruposec', 3)->value('uidSecretario'));
    }

    public function test_acta_valida_fecha_horas_y_firmantes_distintos(): void
    {
        // Periodo 112 de licenciatura: 2026-08-03 a 2026-08-16; profesor del grupo 1: 101
        $datos = ['uidSecretario' => 103, 'uidPresidente' => 104, 'uidSupervisor' => 105, 'fechaIni' => '2026-08-14', 'horaIni' => '09:00', 'horaFin' => '11:00'];
        $antes = DB::table('grupos')->where('gruposec', 1)->first();
        $error = function ($cambios, $campo, $mensaje) use ($datos) {
            $this->postJson('/api/grupos/actas/1', array_merge($datos, $cambios))
                ->assertStatus(422)
                ->assertJsonPath('errors.'.$campo.'.0', $mensaje);
        };

        // 3. Fecha dentro del periodo, horas válidas y fin después de inicio
        $error(['fechaIni' => '2026-08-02'], 'fechaIni', 'La fecha del acta debe quedar dentro del periodo (03/08/2026 al 16/08/2026).');
        $error(['fechaIni' => '2026-08-17'], 'fechaIni', 'La fecha del acta debe quedar dentro del periodo (03/08/2026 al 16/08/2026).');
        $error(['fechaIni' => '14/08/2026'], 'fechaIni', 'Captura la fecha del acta (AAAA-MM-DD).');
        $error(['horaFin' => '09:00'], 'horaFin', 'La hora de fin debe ser posterior a la hora de inicio.');
        $error(['horaFin' => '08:30'], 'horaFin', 'La hora de fin debe ser posterior a la hora de inicio.');
        $error(['horaFin' => '7pm'], 'horaFin', 'Captura la hora de fin del acta (HH:MM).');
        $error(['horaIni' => '25:00'], 'horaIni', 'Captura la hora de inicio del acta (HH:MM).');

        // 4. Firmantes distintos entre sí y distintos del profesor
        $error(['uidPresidente' => 103], 'uidPresidente', 'El presidente no puede ser la misma persona que el secretario.');
        $error(['uidSupervisor' => 104], 'uidSupervisor', 'El supervisor no puede ser la misma persona que el presidente.');
        $error(['uidSecretario' => 101], 'uidSecretario', 'El secretario no puede ser el profesor de la asignatura.');
        $error(['uidSupervisor' => 101], 'uidSupervisor', 'El supervisor no puede ser el profesor de la asignatura.');

        $this->assertEquals($antes, DB::table('grupos')->where('gruposec', 1)->first());

        // El presidente sí puede ser el profesor (es su predeterminado), pero no repetirse con otro firmante
        $this->postJson('/api/grupos/actas/1', array_merge($datos, ['uidPresidente' => 101]))->assertOk();
        $this->assertSame(101, (int) DB::table('grupos')->where('gruposec', 1)->value('uidPresidente'));
        $error(['uidPresidente' => 101, 'uidSecretario' => 101], 'uidSecretario', 'El secretario no puede ser el profesor de la asignatura.');

        // Bordes válidos: primer y último día del periodo, horas con segundos
        $this->postJson('/api/grupos/actas/1', array_merge($datos, ['fechaIni' => '2026-08-03']))->assertOk();
        $this->postJson('/api/grupos/actas/1', array_merge($datos, ['fechaIni' => '2026-08-16', 'horaIni' => '09:00:00', 'horaFin' => '09:50:00']))->assertOk();

        // Sin profesor asignado no se puede guardar el acta
        DB::table('grupos')->where('gruposec', 1)->update(['uidProfesor' => null]);
        $this->postJson('/api/grupos/actas/1', array_merge($datos, ['uidSecretario' => 101]))->assertStatus(422)->assertJsonValidationErrors(['uidProfesor']);
    }

    public function test_firmantes_del_acta_deben_existir_en_persona(): void
    {
        $datos = ['uidSecretario' => 103, 'uidPresidente' => 104, 'uidSupervisor' => 105, 'fechaIni' => '2026-08-14', 'horaIni' => '09:00', 'horaFin' => '11:00'];
        $antes = DB::table('grupos')->where('gruposec', 1)->first();

        foreach (['uidSecretario' => 'secretario', 'uidPresidente' => 'presidente', 'uidSupervisor' => 'supervisor'] as $campo => $rol) {
            $this->postJson('/api/grupos/actas/1', array_merge($datos, [$campo => 999]))
                ->assertStatus(422)
                ->assertJsonValidationErrors([$campo])
                ->assertJsonFragment(['message' => "El $rol no existe en el catálogo de personas."]);
        }

        // Un nombre escrito a mano no es un uid
        $this->postJson('/api/grupos/actas/1', array_merge($datos, ['uidPresidente' => 'PEDRO SOTO']))->assertStatus(422);
        $this->postJson('/api/grupos/actas/1', array_merge(array_diff_key($datos, ['uidSupervisor' => 1]), ['uidVocal' => 999]))->assertStatus(422);
        $this->assertEquals($antes, DB::table('grupos')->where('gruposec', 1)->first());

        // 103 no es empleado en esta base pero si persona: se acepta
        $this->postJson('/api/grupos/actas/1', $datos)->assertOk();
    }

    public function test_acta_sin_logotipos_ni_formato_conserva_lo_guardado(): void
    {
        DB::table('grupos')->where('gruposec', 1)->update(['logoSep' => 'LOGOSEP05', 'idFormato' => 2]);

        $this->postJson('/api/grupos/actas/1', [
            'uidSecretario' => 103,
            'uidPresidente' => 104,
            'uidSupervisor' => 105,
            'fechaIni' => '2026-08-14',
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

    public function test_solo_se_borran_sesiones_no_impartidas_dentro_del_periodo(): void
    {
        // Periodo 112: 2026-08-03 a 2026-08-16
        $base = ['grupoSec' => 1, 'day' => 2];
        DB::table('horarios')->insert([
            $base + ['fechaIni' => '2026-08-03 07:00:00', 'fechaFin' => '2026-08-03 08:40:00', 'impartido' => 1],
            $base + ['fechaIni' => '2026-08-10 07:00:00', 'fechaFin' => '2026-08-10 08:40:00', 'impartido' => 0],
            $base + ['fechaIni' => '2026-08-16 20:00:00', 'fechaFin' => '2026-08-16 20:50:00', 'impartido' => null],
            $base + ['fechaIni' => '2026-07-27 07:00:00', 'fechaFin' => '2026-07-27 08:40:00', 'impartido' => 0],
            $base + ['fechaIni' => '2026-08-17 07:00:00', 'fechaFin' => '2026-08-17 08:40:00', 'impartido' => 0],
        ]);

        // Guardar regenera: borra los pendientes del periodo y conserva impartidas y lo de fuera
        $this->putJson('/api/horarios/1', ['dias' => [['day' => 2, 'horaIni' => '09:00', 'sesiones' => 2]]])->assertOk();
        $fechas = DB::table('horarios')->where('grupoSec', 1)->orderBy('fechaIni')->pluck('fechaIni')->all();
        $this->assertSame(['2026-07-27 07:00:00', '2026-08-03 07:00:00', '2026-08-10 09:00:00', '2026-08-17 07:00:00'], $fechas);

        // Eliminar horario: igual, solo lo pendiente dentro del periodo
        $this->deleteJson('/api/horarios/1')->assertOk()->assertJsonFragment(['message' => 'Horario eliminado; se conservaron 1 sesiones impartidas']);
        $fechas = DB::table('horarios')->where('grupoSec', 1)->orderBy('fechaIni')->pluck('fechaIni')->all();
        $this->assertSame(['2026-07-27 07:00:00', '2026-08-03 07:00:00', '2026-08-17 07:00:00'], $fechas);
    }

    public function test_horario_respeta_dias_y_horas_del_turno(): void
    {
        $previa = function ($dia, $horaIni, $sesiones) {
            return $this->postJson('/api/horarios/vista-previa', ['grupoSec' => 1, 'dias' => [['day' => $dia, 'horaIni' => $horaIni, 'sesiones' => $sesiones]]]);
        };

        // Dentro del turno matutino: lunes 12:20 + 2 x 50 = 14:00 justo al cierre
        $previa(2, '12:20', 2)->assertOk();
        $previa(2, '07:00', 1)->assertOk();

        $previa(7, '08:00', 1)->assertStatus(400)->assertJsonFragment(['message' => 'El turno del grupo no tiene clases el SÁBADO']);
        $previa(2, '06:30', 1)->assertStatus(400)->assertJsonFragment(['message' => 'La clase del LUNES debe quedar entre 07:00 y 14:00, el horario del turno']);
        // La hora fin se calcula: 13:30 + 50 = 14:20 rebasa el turno
        $previa(2, '13:30', 1)->assertStatus(400);
        $this->putJson('/api/horarios/1', ['dias' => [['day' => 2, 'horaIni' => '12:20', 'sesiones' => 3]]])->assertStatus(400);

        // Sin detalle del turno no se restringe
        DB::table('dtlTurno')->where('idTurno', 1)->delete();
        $previa(7, '08:00', 1)->assertOk();
    }

    private function previaGrupo1($dia, $horaIni, $sesiones)
    {
        return $this->postJson('/api/horarios/vista-previa', ['grupoSec' => 1, 'dias' => [['day' => $dia, 'horaIni' => $horaIni, 'sesiones' => $sesiones]]]);
    }

    public function test_horario_usa_los_dias_del_turno_del_nivel_del_grupo(): void
    {
        // Los días generales (sin nivel) del matutino son lun-vie 07:00-14:00; licenciatura (5) tiene su propio
        // horario: solo lunes y martes de 09:00 a 12:00
        DB::table('dtlTurno')->insert([
            ['idTurno' => 1, 'diaSemana' => '2', 'horaInicio' => '09:00:00', 'horaFin' => '12:00:00', 'idNivel' => 5],
            ['idTurno' => 1, 'diaSemana' => '3', 'horaInicio' => '09:00:00', 'horaFin' => '12:00:00', 'idNivel' => 5],
            // Otro nivel no debe influir en el grupo de licenciatura
            ['idTurno' => 1, 'diaSemana' => '4', 'horaInicio' => '06:00:00', 'horaFin' => '22:00:00', 'idNivel' => 6],
        ]);

        $this->previaGrupo1(2, '09:00', 1)->assertOk();
        $this->previaGrupo1(2, '08:00', 1)->assertStatus(400)
            ->assertJsonFragment(['message' => 'La clase del LUNES debe quedar entre 09:00 y 12:00, el horario del turno']);
        // El miércoles solo existe en los generales y en maestría; licenciatura no lo tiene
        $this->previaGrupo1(4, '09:00', 1)->assertStatus(400)
            ->assertJsonFragment(['message' => 'El turno del grupo no tiene clases el MIÉRCOLES']);

        // Un nivel sin días propios usa los generales
        $this->grupo(8, ['idNivel' => 4, 'grupo' => '01M1A']);
        DB::table('periodo')->insert(['idNivel' => 4, 'idPeriodo' => 112, 'fechaInicio' => '2026-08-03', 'fechaTermino' => '2026-08-16']);
        DB::table('duracionSesion')->insert(['idNivel' => 4, 'idTurno' => 1, 'duracion' => 50]);
        $this->postJson('/api/horarios/vista-previa', ['grupoSec' => 8, 'dias' => [['day' => 4, 'horaIni' => '07:00', 'sesiones' => 1]]])->assertOk();
    }

    public function test_turnos_detalles_por_nivel(): void
    {
        DB::table('dtlTurno')->insert([
            ['idTurno' => 1, 'diaSemana' => '2', 'horaInicio' => '09:00:00', 'horaFin' => '12:00:00', 'idNivel' => 5],
            ['idTurno' => 1, 'diaSemana' => '2', 'horaInicio' => '06:30:00', 'horaFin' => '13:00:00', 'idNivel' => 6],
        ]);
        $dias = function ($respuesta, $idTurno) {
            $turno = collect($respuesta->json('turnos'))->firstWhere('idTurno', $idTurno);
            return collect($turno['diasTurno'])->map(function ($d) { return $d['diaSemana'].' '.substr($d['horaInicio'], 0, 5).'-'.substr($d['horaFin'], 0, 5).' '.json_encode($d['idNivel']); })->all();
        };

        // Licenciatura: solo su lunes
        $this->assertSame(['2 09:00-12:00 5'], $dias($this->getJson('/api/turnos/detalles?idNivel=5')->assertOk(), 1));
        // Bachillerato no tiene propios: los generales
        $this->assertCount(5, $dias($this->getJson('/api/turnos/detalles?idNivel=4'), 1));
        // Sin nivel: un registro por día con el horario más amplio entre niveles
        $sinNivel = $dias($this->getJson('/api/turnos/detalles'), 1);
        $this->assertCount(5, $sinNivel);
        $this->assertSame('2 06:30-14:00 null', $sinNivel[0]);
    }

    public function test_horario_no_ocupa_bloques_del_turno(): void
    {
        DB::table('tipoBloque')->insert([
            ['idTipoBloque' => 1, 'descripcion' => 'RECESO', 'color' => '#FFFF00', 'activo' => 1],
        ]);
        DB::table('defaultTurno')->insert([
            ['idTurno' => 1, 'horaInicio' => '09:40:00', 'idTipoBloque' => 1, 'horaFin' => '10:00:00', 'activo' => 1],
            ['idTurno' => 1, 'horaInicio' => '12:00:00', 'idTipoBloque' => 1, 'horaFin' => '12:20:00', 'activo' => 0],
            ['idTurno' => 2, 'horaInicio' => '08:00:00', 'idTipoBloque' => 1, 'horaFin' => '09:00:00', 'activo' => 1],
        ]);

        $this->previaGrupo1(2, '09:00', 1)->assertStatus(400)
            ->assertJsonFragment(['message' => 'La clase del LUNES se traslapa con RECESO del turno (09:40 - 10:00)']);
        // Justo antes y justo despues del receso; el bloque inactivo no cuenta
        $this->previaGrupo1(2, '08:50', 1)->assertOk();
        $this->previaGrupo1(2, '10:00', 1)->assertOk();
        $this->previaGrupo1(2, '11:40', 2)->assertOk();

        $this->getJson('/api/horarios/1')->assertOk()
            ->assertJsonPath('horario.bloquesTurno', [['horaIni' => '09:40', 'horaFin' => '10:00', 'idTipoBloque' => 1, 'descripcion' => 'RECESO', 'color' => '#FFFF00']]);
    }

    public function test_horario_no_se_traslapa_con_otras_asignaturas_del_grupo(): void
    {
        DB::table('asignatura')->insert(['idAsignatura' => 'LIAE41.II', 'descripcion' => 'PROBLEMAS SOCIOECONOMICOS']);
        $this->grupo(4, ['idAsignatura' => 'LIAE41.II']);
        DB::table('horarios')->insert([
            ['grupoSec' => 4, 'fechaIni' => '2026-08-03 08:00:00', 'fechaFin' => '2026-08-03 09:40:00', 'day' => 2],
            ['grupoSec' => 4, 'fechaIni' => '2026-08-10 08:00:00', 'fechaFin' => '2026-08-10 09:40:00', 'day' => 2],
        ]);

        $this->previaGrupo1(2, '09:00', 1)->assertStatus(400)
            ->assertJsonFragment(['message' => 'La clase el LUNES 03/08/2026 (09:00 - 09:50) se traslapa con LIAE41.II del mismo grupo 06M1A']);
        $this->putJson('/api/horarios/1', ['dias' => [['day' => 2, 'horaIni' => '07:30', 'sesiones' => 1]]])->assertStatus(400);
        $this->previaGrupo1(2, '09:40', 1)->assertOk();
        $this->previaGrupo1(3, '08:00', 1)->assertOk();

        // Otro grupo con la misma asignatura no cuenta como mismo grupo
        $this->assertSame([[
            'tipo' => 'grupo', 'grupoSec' => 4, 'grupo' => '06M1A', 'idAsignatura' => 'LIAE41.II',
            'asignatura' => 'PROBLEMAS SOCIOECONOMICOS', 'day' => 2, 'horaIni' => '08:00', 'horaFin' => '09:40',
        ]], $this->getJson('/api/horarios/1')->json('horario.ocupados'));
    }

    public function test_horario_no_se_traslapa_con_otras_clases_del_profesor(): void
    {
        // El grupo 3 es de otra clave; se le asigna el mismo profesor que el grupo 1
        DB::table('grupos')->where('gruposec', 3)->update(['uidProfesor' => 101]);
        DB::table('horarios')->insert([
            ['grupoSec' => 3, 'fechaIni' => '2026-08-04 10:00:00', 'fechaFin' => '2026-08-04 10:50:00', 'day' => 3],
            // Fuera de las fechas del periodo (termina el 16/08): no estorba
            ['grupoSec' => 3, 'fechaIni' => '2026-09-01 12:00:00', 'fechaFin' => '2026-09-01 12:50:00', 'day' => 3],
        ]);

        $this->previaGrupo1(3, '09:30', 2)->assertStatus(400)
            ->assertJsonFragment(['message' => 'El profesor ya tiene clase el MARTES 04/08/2026 (09:30 - 11:10) en el grupo 04M2A (LIAE05.I)']);
        $this->previaGrupo1(3, '10:50', 1)->assertOk();
        $this->previaGrupo1(3, '12:00', 1)->assertOk();

        $ocupados = $this->getJson('/api/horarios/1')->json('horario.ocupados');
        $this->assertCount(1, $ocupados);
        $this->assertSame(['profesor', '04M2A', 3, '10:00', '10:50'], [$ocupados[0]['tipo'], $ocupados[0]['grupo'], $ocupados[0]['day'], $ocupados[0]['horaIni'], $ocupados[0]['horaFin']]);

        // Sin profesor asignado no hay restriccion por profesor
        DB::table('grupos')->where('gruposec', 1)->update(['uidProfesor' => null]);
        $this->previaGrupo1(3, '09:30', 2)->assertOk();
    }

    public function test_oferta_solo_muestra_asignaturas_del_plan_del_nivel(): void
    {
        DB::table('asignatura')->insert([
            ['idAsignatura' => 'B-23-UAE101', 'descripcion' => 'LA MATERIA Y SUS INTERACCIONES'],
            ['idAsignatura' => 'SIN-PLAN', 'descripcion' => 'MATERIA SIN PLAN'],
        ]);
        // B-23-UAE101 es del plan de bachillerato (nivel 4); SIN-PLAN no está en ningún plan
        DB::table('detasignatura')->insert(['idAsignatura' => 'B-23-UAE101', 'idNivel' => 4, 'idCarrera' => 1]);

        // Capturadas en un grupo de licenciatura de la carrera 06: no deben salir en licenciatura
        $this->grupo(20, ['idAsignatura' => 'B-23-UAE101']);
        $this->grupo(21, ['idAsignatura' => 'SIN-PLAN']);
        $claves = array_column($this->getJson('/api/grupos/oferta/5/112/6')->assertOk()->json('grupos'), 'idAsignatura');
        $this->assertNotContains('B-23-UAE101', $claves);
        $this->assertNotContains('SIN-PLAN', $claves);
        $this->assertContains('LIAE05.I', $claves);

        // Una materia de licenciatura capturada en un grupo de bachillerato tampoco sale en bachillerato;
        // la de su plan sí
        $this->grupo(22, ['idNivel' => 4, 'grupo' => '01M1A', 'idAsignatura' => 'LIAE05.I']);
        $this->grupo(23, ['idNivel' => 4, 'grupo' => '01M1A', 'idAsignatura' => 'B-23-UAE101']);
        DB::table('periodo')->insert(['idNivel' => 4, 'idPeriodo' => 112, 'fechaInicio' => '2026-08-03', 'fechaTermino' => '2026-08-16']);
        $this->assertSame(['B-23-UAE101'], array_column($this->getJson('/api/grupos/oferta/4/112/1')->assertOk()->json('grupos'), 'idAsignatura'));
    }

    public function test_ruta_de_asignaturas_de_la_oferta_carga_su_controlador(): void
    {
        $this->getJson('/api/asignaturas/oferta/5/112/6')
            ->assertOk()
            ->assertJsonPath('asignaturas.0.idAsignatura', 'LIAE05.I');
    }
}
