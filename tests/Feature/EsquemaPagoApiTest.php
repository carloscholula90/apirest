<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Flujo del esquema de pago en SQLite en memoria, con las columnas que dejan las migraciones 2026_10_28 y 2026_11_09
class EsquemaPagoApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'pago_test',
            'database.connections.pago_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('pago_test');

        Schema::create('turno', function (Blueprint $table) {
            $table->integer('idTurno')->primary();
            $table->string('descripcion');
        });
        Schema::create('dtlTurno', function (Blueprint $table) {
            $table->increments('idDtlTurno');
            $table->integer('idTurno');
            $table->string('diaSemana');
            $table->string('horaInicio');
            $table->string('horaFin');
        });
        Schema::create('tipoBloque', function (Blueprint $table) {
            $table->increments('idTipoBloque');
            $table->string('descripcion');
            $table->string('color')->nullable();
            $table->integer('activo')->default(1);
            $table->integer('pagable')->default(1);
        });
        Schema::create('horarioProfesor', function (Blueprint $table) {
            $table->integer('uid');
            $table->integer('secuencia');
            $table->date('fechaInicio');
            $table->date('fechaFin');
            $table->string('diaSemana');
            $table->string('horaInicio');
            $table->string('horaFin');
            $table->integer('idTipoBloque');
        });
        Schema::create('calendarioPago', function (Blueprint $table) {
            $table->increments('idCalendarioPago');
            $table->integer('idTurno');
            $table->date('fechaInicio');
            $table->date('fechaFin');
            $table->integer('procesado')->default(0);
            $table->dateTime('fechaCreacion')->nullable();
        });
        Schema::create('regimenFiscalNomina', function (Blueprint $table) {
            $table->increments('idRegimenFiscal');
            $table->string('descripcion');
            $table->integer('activo')->default(1);
        });
        Schema::create('costoHoraRegimen', function (Blueprint $table) {
            $table->increments('idCostoHora');
            $table->integer('idRegimenFiscal');
            $table->decimal('costoHora', 10, 2);
            $table->date('vigenciaInicio');
            $table->date('vigenciaFin')->nullable();
        });
        Schema::create('regimenFiscalImpuesto', function (Blueprint $table) {
            $table->increments('idRegimenFiscalImpuesto');
            $table->integer('idRegimenFiscal');
            $table->integer('idImpuesto');
            $table->integer('activo')->default(1);
            $table->dateTime('fechaCreacion')->nullable();
        });
        Schema::create('impuestos', function (Blueprint $table) {
            $table->integer('idImpuesto')->primary();
            $table->string('descripcion');
            $table->integer('isPorcentaje')->default(1);
            $table->decimal('valor', 10, 2)->default(0);
            $table->decimal('porcentaje', 10, 4)->default(0);
            $table->integer('esRetencion')->default(0);
            $table->integer('activo')->default(1);
            $table->dateTime('fechaCreacion')->nullable();
        });
        Schema::create('persona', function (Blueprint $table) {
            $table->integer('uid');
            $table->string('nombre');
            $table->string('primerApellido')->nullable();
            $table->string('segundoApellido')->nullable();
        });
        Schema::create('empleado', function (Blueprint $table) {
            $table->integer('uid');
            $table->integer('secuencia');
            $table->integer('idRegimenFiscal')->default(0);
            $table->integer('idRegimenFiscalNomina')->nullable();
            $table->date('fechabaja')->nullable();
        });
        Schema::create('pago', function (Blueprint $table) {
            $table->increments('idPago');
            $table->integer('idCalendarioPago');
            $table->integer('uid');
            $table->integer('idRegimenFiscal')->nullable();
            $table->decimal('costoPorHora', 10, 2);
            $table->decimal('totalHoras', 10, 2);
            $table->decimal('horasADescontar', 10, 2)->default(0);
            $table->decimal('netoAPagar', 12, 2);
            $table->dateTime('fechaCreacion')->nullable();
            $table->unique(['idCalendarioPago', 'uid']);
        });
        Schema::create('detallePago', function (Blueprint $table) {
            $table->increments('idDetallePago');
            $table->integer('idPago');
            $table->integer('idRegimenFiscalImpuesto')->nullable();
            $table->integer('idImpuesto');
            $table->string('descripcion');
            $table->decimal('porcentaje', 10, 4);
            $table->integer('esRetencion');
            $table->decimal('importe', 12, 2);
            $table->dateTime('fechaCreacion')->nullable();
        });

        DB::table('turno')->insert(['idTurno' => 1, 'descripcion' => "MATUTINO\r"]);
        DB::table('dtlTurno')->insert(['idTurno' => 1, 'diaSemana' => 'LUNES', 'horaInicio' => '07:00', 'horaFin' => '14:00']);
        DB::table('tipoBloque')->insert([
            ['idTipoBloque' => 1, 'descripcion' => 'CLASE', 'activo' => 1, 'pagable' => 1],
            ['idTipoBloque' => 2, 'descripcion' => 'TUTORIA', 'activo' => 1, 'pagable' => 0],
        ]);
        DB::table('persona')->insert([
            ['uid' => 100, 'nombre' => 'ANA', 'primerApellido' => 'PRUEBA', 'segundoApellido' => null],
            ['uid' => 101, 'nombre' => 'LUIS', 'primerApellido' => 'EJEMPLO', 'segundoApellido' => 'UNO'],
        ]);
        DB::table('empleado')->insert([
            ['uid' => 100, 'secuencia' => 1],
            ['uid' => 101, 'secuencia' => 1],
        ]);
        // 100 minutos de clase cada lunes (2 horas clase) y 50 de tutoria que no se paga
        DB::table('horarioProfesor')->insert([
            ['uid' => 100, 'secuencia' => 1, 'fechaInicio' => '2026-08-01', 'fechaFin' => '2026-12-31', 'diaSemana' => 'LUNES', 'horaInicio' => '07:00', 'horaFin' => '08:40', 'idTipoBloque' => 1],
            ['uid' => 100, 'secuencia' => 2, 'fechaInicio' => '2026-08-01', 'fechaFin' => '2026-12-31', 'diaSemana' => 'LUNES', 'horaInicio' => '09:00', 'horaFin' => '09:50', 'idTipoBloque' => 2],
            ['uid' => 101, 'secuencia' => 1, 'fechaInicio' => '2026-08-01', 'fechaFin' => '2026-12-31', 'diaSemana' => 'LUNES', 'horaInicio' => '10:00', 'horaFin' => '10:50', 'idTipoBloque' => 1],
        ]);
    }

    private function datos($respuesta, $clave)
    {
        return $respuesta->json($clave);
    }

    public function test_impuesto_guarda_porcentaje_en_valor(): void
    {
        $alta = $this->postJson('/api/impuestos/create', ['descripcion' => 'iva', 'porcentaje' => 16, 'esRetencion' => 0]);
        $alta->assertStatus(200);

        $fila = DB::table('impuestos')->where('idImpuesto', 1)->first();
        $this->assertSame('IVA', $fila->descripcion);
        $this->assertEquals(16, $fila->porcentaje);
        $this->assertEquals(16, $fila->valor);
        $this->assertEquals(1, $fila->isPorcentaje);

        $this->putJson('/api/impuestos/1', ['idImpuesto' => 1, 'descripcion' => 'IVA', 'porcentaje' => 8])->assertStatus(200);
        $fila = DB::table('impuestos')->where('idImpuesto', 1)->first();
        $this->assertEquals(8, $fila->porcentaje);
        $this->assertEquals(8, $fila->valor);

        // Sin porcentaje no se toca el valor
        $this->putJson('/api/impuestos/1', ['idImpuesto' => 1, 'descripcion' => 'IVA TRASLADADO'])->assertStatus(200);
        $this->assertEquals(8, DB::table('impuestos')->where('idImpuesto', 1)->value('valor'));
    }

    public function test_tipo_bloque_guarda_pagable(): void
    {
        $this->postJson('/api/tipobloque/create', ['descripcion' => 'comida', 'color' => '#cccccc', 'activo' => 1, 'pagable' => 0])->assertStatus(200);
        $this->assertEquals(0, DB::table('tipoBloque')->where('idTipoBloque', 3)->value('pagable'));
    }

    public function test_flujo_completo_de_pago(): void
    {
        // Catalogo fiscal
        $regimen = $this->postJson('/api/regimenesfiscales/create', ['descripcion' => 'honorarios']);
        $regimen->assertStatus(200);
        $idRegimen = $regimen->json('regimenFiscal.idRegimenFiscal');
        $this->assertNotNull($idRegimen);

        $this->postJson("/api/regimenesfiscales/$idRegimen/costos", ['costoHora' => 200, 'vigenciaInicio' => '2026-01-01'])->assertStatus(200);

        foreach ([['IVA', 16, 0], ['RET ISR', 10, 1], ['RET IVA', 10, 1]] as [$descripcion, $porcentaje, $retencion]) {
            $impuesto = $this->postJson('/api/impuestos/create', ['descripcion' => $descripcion, 'porcentaje' => $porcentaje, 'esRetencion' => $retencion]);
            $impuesto->assertStatus(200);
            $this->postJson("/api/regimenesfiscales/$idRegimen/impuestos", ['idImpuesto' => $impuesto->json('$impuestos.idImpuesto')])->assertStatus(200);
        }
        $this->assertCount(3, $this->getJson("/api/regimenesfiscales/$idRegimen/impuestos")->json('impuestos'));

        // Antes de asignar, los dos empleados aparecen sin regimen
        $this->assertCount(2, $this->getJson('/api/regimenesfiscales/sinregimen')->json('empleados'));
        $this->putJson("/api/regimenesfiscales/$idRegimen/empleados/100")->assertStatus(200);
        $this->putJson("/api/regimenesfiscales/$idRegimen/empleados/999")->assertStatus(404);
        $this->assertCount(1, $this->getJson('/api/regimenesfiscales/sinregimen')->json('empleados'));
        $this->assertEquals(1, collect($this->getJson('/api/regimenesfiscales')->json('regimenesFiscales'))->firstWhere('idRegimenFiscal', $idRegimen)['empleados']);

        // Calendario de dos semanas: lunes 2 y 9 de noviembre
        $calendario = $this->postJson('/api/calendariopago/create', ['idTurno' => 1, 'fechaInicio' => '2026-11-02', 'fechaFin' => '2026-11-15']);
        $calendario->assertStatus(200);
        $idCalendario = $calendario->json('calendarioPago.idCalendarioPago');

        $this->postJson('/api/calendariopago/create', ['idTurno' => 1, 'fechaInicio' => '2026-11-10', 'fechaFin' => '2026-11-20'])->assertStatus(400);
        $this->assertEquals($idCalendario, $this->getJson('/api/calendariopago/siguiente')->json('calendarioPago.idCalendarioPago'));

        // Vista previa: 4 horas x 200 = 800; +16% IVA, -10% ISR, -10% IVA retenido = 768
        $previa = $this->getJson("/api/pagos/concentrado/$idCalendario");
        $previa->assertStatus(200);
        $filas = $previa->json('concentrado');
        $this->assertCount(1, $filas);
        $this->assertEquals(4, $filas[0]['horasTrabajadas']);
        $this->assertEquals(800, $filas[0]['honorarios']);
        $this->assertEquals(128, $filas[0]['iva']);
        $this->assertEquals(80, $filas[0]['retencionIsr']);
        $this->assertEquals(80, $filas[0]['retencionIva']);
        $this->assertEquals(768, $filas[0]['neto']);
        $this->assertSame('PRUEBA ANA', $filas[0]['nombre']);
        $this->assertSame('No tiene regimen fiscal asignado', $previa->json('omitidos.0.motivo'));

        // Procesar guarda pago y detalle y cierra el calendario
        $this->postJson("/api/pagos/procesar/$idCalendario")->assertStatus(200)->assertJson(['pagos' => 1]);
        $this->assertEquals(1, DB::table('pago')->count());
        $this->assertEquals(3, DB::table('detallePago')->count());
        $this->assertEquals(1, DB::table('calendarioPago')->where('idCalendarioPago', $idCalendario)->value('procesado'));

        $guardado = $this->getJson("/api/pagos/concentrado/$idCalendario")->json('concentrado');
        $this->assertEquals(768, $guardado[0]['neto']);
        $this->assertEquals(128, $guardado[0]['iva']);

        // Ya procesado: no se reprocesa ni se edita; el impuesto usado se desactiva para conservar el historico
        $this->postJson("/api/pagos/procesar/$idCalendario")->assertStatus(400);
        $this->putJson("/api/calendariopago/$idCalendario", ['fechaInicio' => '2026-11-02', 'fechaFin' => '2026-11-16', 'procesado' => 0])->assertStatus(400);
        $this->deleteJson("/api/regimenesfiscales/$idRegimen/impuestos/1")->assertStatus(200);
        $this->assertEquals(0, DB::table('regimenFiscalImpuesto')->where('idImpuesto', 1)->value('activo'));
        $this->assertEquals(768, $this->getJson("/api/pagos/concentrado/$idCalendario")->json('concentrado.0.neto'));
        $this->assertNull($this->getJson('/api/calendariopago/siguiente')->json('calendarioPago'));
    }

    public function test_procesar_sin_pagables_no_guarda(): void
    {
        DB::table('calendarioPago')->insert(['idTurno' => 1, 'fechaInicio' => '2026-11-03', 'fechaFin' => '2026-11-07', 'procesado' => 0]);

        // Martes a sabado: el turno solo tiene lunes
        $this->postJson('/api/pagos/procesar/1')->assertStatus(400);
        $this->assertEquals(0, DB::table('pago')->count());
        $this->assertEquals(0, DB::table('calendarioPago')->value('procesado'));
    }

    public function test_migracion_reemplaza_pago_anterior_vacio_y_copia_porcentaje(): void
    {
        Schema::drop('pago');
        Schema::create('pago', function (Blueprint $table) {
            $table->integer('idTurno');
            $table->date('fechaInicio');
            $table->integer('uid');
            $table->integer('secuencia');
            $table->decimal('netoAPagar', 12, 2)->default(0);
        });
        DB::table('impuestos')->insert(['idImpuesto' => 5, 'descripcion' => 'IVA', 'isPorcentaje' => 1, 'valor' => 16, 'porcentaje' => 0]);

        $migracion = require base_path('database/migrations/2026_11_09_000001_ajusta_pago_e_impuestos.php');
        $migracion->up();

        $this->assertTrue(Schema::hasColumn('pago', 'idPago'));
        $this->assertTrue(Schema::hasColumn('pago', 'idCalendarioPago'));
        $this->assertEquals(16, DB::table('impuestos')->where('idImpuesto', 5)->value('porcentaje'));

        // Correrla otra vez no cambia nada
        $migracion->up();
        $this->assertTrue(Schema::hasColumn('pago', 'idPago'));
    }

    public function test_migracion_se_detiene_si_pago_anterior_tiene_datos(): void
    {
        Schema::drop('pago');
        Schema::create('pago', function (Blueprint $table) {
            $table->integer('idTurno');
            $table->integer('uid');
        });
        DB::table('pago')->insert(['idTurno' => 1, 'uid' => 100]);

        $this->expectException(\RuntimeException::class);
        (require base_path('database/migrations/2026_11_09_000001_ajusta_pago_e_impuestos.php'))->up();
    }
}
