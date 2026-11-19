<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Reporte de alumnos inscritos por rango de fechas (alumno.fechaAlta), en SQLite en memoria
class ReporteAlumnosInscritosApiTest extends TestCase
{
    private $generados = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'inscritos_test',
            'database.connections.inscritos_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('inscritos_test');

        Schema::create('alumno', function (Blueprint $table) {
            $table->integer('uid');
            $table->integer('secuencia');
            $table->integer('idNivel')->nullable();
            $table->integer('idCarrera')->nullable();
            $table->integer('activo')->nullable();
            $table->dateTime('fechaAlta')->nullable();
        });
        Schema::create('persona', function (Blueprint $table) {
            $table->integer('uid');
            $table->string('nombre');
            $table->string('primerApellido')->nullable();
            $table->string('segundoApellido')->nullable();
        });
        Schema::create('carrera', function (Blueprint $table) {
            $table->integer('idNivel');
            $table->integer('idCarrera');
            $table->string('descripcion');
        });

        DB::table('carrera')->insert(['idNivel' => 5, 'idCarrera' => 6, 'descripcion' => "CIENCIAS DE LA COMUNICACION\r"]);
        DB::table('persona')->insert([
            ['uid' => 101, 'nombre' => 'ANA', 'primerApellido' => 'LOPEZ', 'segundoApellido' => 'RUIZ'],
            ['uid' => 102, 'nombre' => 'PEDRO', 'primerApellido' => 'SOTO', 'segundoApellido' => null],
            ['uid' => 103, 'nombre' => 'SARA', 'primerApellido' => 'DIAZ', 'segundoApellido' => 'PEREZ'],
            ['uid' => 104, 'nombre' => 'LUIS', 'primerApellido' => 'CANO', 'segundoApellido' => 'VEGA'],
        ]);
        DB::table('alumno')->insert([
            // Primer instante y último instante del rango 2026-08-01 a 2026-08-31
            ['uid' => 101, 'secuencia' => 1, 'idNivel' => 5, 'idCarrera' => 6, 'activo' => 1, 'fechaAlta' => '2026-08-01 00:00:00'],
            ['uid' => 102, 'secuencia' => 1, 'idNivel' => 5, 'idCarrera' => 6, 'activo' => 0, 'fechaAlta' => '2026-08-31 23:59:59'],
            // Carrera que no existe en el catálogo: sale sin carrera
            ['uid' => 103, 'secuencia' => 1, 'idNivel' => 5, 'idCarrera' => 99, 'activo' => 1, 'fechaAlta' => '2026-08-15 10:00:00'],
            // Fuera del rango
            ['uid' => 104, 'secuencia' => 1, 'idNivel' => 5, 'idCarrera' => 6, 'activo' => 1, 'fechaAlta' => '2026-09-01 00:00:00'],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->generados as $archivo) {
            @unlink($archivo);
        }
        parent::tearDown();
    }

    private function archivo($respuesta)
    {
        $url = $respuesta->assertOk()->json('message');
        $ruta = storage_path('app/public/'.basename($url));
        $this->assertFileExists($ruta);
        $this->generados[] = $ruta;

        return [$url, $ruta];
    }

    public function test_excel_trae_los_alumnos_del_rango_con_sus_columnas(): void
    {
        [$url, $ruta] = $this->archivo($this->getJson('/api/alumnos/inscritosFechasExc/2026-08-01/2026-08-31'));
        $this->assertMatchesRegularExpression('#^https://reportes\.siaweb\.com\.mx/storage/app/public/alumnosInscritosRpt_\d{8}_\d{6}_[0-9a-f]{6}\.xlsx$#', $url);

        $lector = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $lector->setReadDataOnly(true);
        $filas = $lector->load($ruta)->getActiveSheet()->toArray(null, true, false);

        $this->assertSame('REPORTE DE ALUMNOS INSCRITOS', $filas[0][0]);
        $this->assertSame('DEL 01/08/2026 AL 31/08/2026', $filas[1][0]);
        $this->assertSame(['UID', 'NOMBRE', 'CARRERA', 'FECHA DE INSCRIPCIÓN'], $filas[3]);
        // Ordenados por fecha de alta; incluye inactivos y al que no tiene carrera en el catálogo
        $this->assertSame([101, 'LOPEZ RUIZ ANA', 'CIENCIAS DE LA COMUNICACION', '01/08/2026'], $filas[4]);
        $this->assertSame([103, 'DIAZ PEREZ SARA', null, '15/08/2026'], [$filas[5][0], $filas[5][1], $filas[5][2] ?: null, $filas[5][3]]);
        $this->assertSame([102, 'SOTO PEDRO', 'CIENCIAS DE LA COMUNICACION', '31/08/2026'], $filas[6]);
        $this->assertSame(['TOTAL DE ALUMNOS', 3], [$filas[8][0], (int) $filas[8][1]]);
    }

    public function test_pdf_se_genera_con_nombre_unico(): void
    {
        // pdfController (compartido) deja abierto un búfer de salida; se cierra aquí para no marcar la prueba
        $nivel = ob_get_level();
        [$url1] = $this->archivo($this->getJson('/api/alumnos/inscritosFechas/2026-08-01/2026-08-31'));
        [$url2] = $this->archivo($this->getJson('/api/alumnos/inscritosFechas/2026-08-01/2026-08-31'));
        while (ob_get_level() > $nivel) {
            ob_end_clean();
        }

        $this->assertMatchesRegularExpression('#/rptAlumnosInscritos_\d{8}_\d{6}_[0-9a-f]{6}\.pdf$#', $url1);
        $this->assertNotSame($url1, $url2);
    }

    public function test_un_solo_dia(): void
    {
        [, $ruta] = $this->archivo($this->getJson('/api/alumnos/inscritosFechasExc/2026-08-15/2026-08-15'));
        $lector = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $lector->setReadDataOnly(true);
        $this->assertSame(103, $lector->load($ruta)->getActiveSheet()->toArray(null, true, false)[4][0]);
    }

    public function test_valida_fechas(): void
    {
        foreach (['inscritosFechas', 'inscritosFechasExc'] as $ruta) {
            $this->getJson("/api/alumnos/$ruta/2026-08-31/2026-08-01")->assertStatus(400)
                ->assertJsonPath('error.fechaFin.0', 'La fecha de fin no puede ser anterior a la fecha de inicio.');
            $this->getJson("/api/alumnos/$ruta/01-08-2026/2026-08-31")->assertStatus(400)
                ->assertJsonPath('error.fechaInicio.0', 'La fecha de inicio debe tener el formato AAAA-MM-DD.');
            $this->getJson("/api/alumnos/$ruta/2026-08-01/2026-02-30")->assertStatus(400)
                ->assertJsonPath('error.fechaFin.0', 'La fecha de fin debe tener el formato AAAA-MM-DD.');
        }
    }

    public function test_rango_sin_alumnos(): void
    {
        $this->getJson('/api/alumnos/inscritosFechas/2025-01-01/2025-01-31')->assertNotFound()
            ->assertJsonPath('message', 'No hay alumnos inscritos del 01/01/2025 al 31/01/2025');
        $this->getJson('/api/alumnos/inscritosFechasExc/2025-01-01/2025-01-31')->assertNotFound();
    }
}
