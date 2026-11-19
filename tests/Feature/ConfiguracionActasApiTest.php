<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Secretario y supervisor predeterminados del acta por nivel (el presidente es el profesor), en SQLite en memoria
class ConfiguracionActasApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'actas_test',
            'database.connections.actas_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('actas_test');

        Schema::create('nivel', function (Blueprint $table) {
            $table->integer('idNivel');
            $table->string('descripcion');
            $table->integer('activo');
        });
        Schema::create('persona', function (Blueprint $table) {
            $table->integer('uid');
            $table->string('nombre');
            $table->string('primerApellido')->nullable();
            $table->string('segundoApellido')->nullable();
        });
        Schema::create('configuracionActas', function (Blueprint $table) {
            $table->integer('idNivel')->nullable();
            $table->integer('idSecretario')->nullable();
            $table->integer('idPresidente')->nullable();
            $table->integer('idSupervisor')->nullable();
        });

        DB::table('nivel')->insert([
            ['idNivel' => 4, 'descripcion' => "BACHILLERATO\r", 'activo' => 1],
            ['idNivel' => 5, 'descripcion' => "LICENCIATURA\r", 'activo' => 1],
            ['idNivel' => 8, 'descripcion' => 'POSDOCTORADO', 'activo' => 0],
        ]);
        DB::table('persona')->insert([
            ['uid' => 101, 'nombre' => 'ANA', 'primerApellido' => 'LOPEZ', 'segundoApellido' => 'RUIZ'],
            ['uid' => 102, 'nombre' => 'PEDRO', 'primerApellido' => 'SOTO', 'segundoApellido' => null],
            ['uid' => 103, 'nombre' => 'SARA', 'primerApellido' => 'DIAZ', 'segundoApellido' => 'PEREZ'],
        ]);
        // idPresidente queda en la tabla pero ya no se usa
        DB::table('configuracionActas')->insert(['idNivel' => 5, 'idSecretario' => 101, 'idPresidente' => 102, 'idSupervisor' => 999]);
    }

    public function test_lista_todos_los_niveles_con_secretario_y_supervisor(): void
    {
        $lista = $this->getJson('/api/configuracionactas')->assertOk()->json('configuracionActas');

        $this->assertSame([4, 5, 8], array_column($lista, 'idNivel'));
        $this->assertSame('LICENCIATURA', $lista[1]['nivel']);
        $this->assertSame(['uid' => 101, 'nombre' => 'LOPEZ RUIZ ANA'], $lista[1]['secretario']);
        // Un uid que ya no existe en persona se ve sin nombre para poder corregirlo
        $this->assertSame(['uid' => 999, 'nombre' => null], $lista[1]['supervisor']);
        $this->assertArrayNotHasKey('presidente', $lista[1]);
        $this->assertSame(['uid' => null, 'nombre' => null], $lista[0]['secretario']);
    }

    public function test_consulta_un_nivel(): void
    {
        $this->getJson('/api/configuracionactas/5')->assertOk()->assertJsonPath('configuracionActas.secretario.uid', 101);
        $this->getJson('/api/configuracionactas/4')->assertOk()->assertJsonPath('configuracionActas.secretario.uid', null);
        $this->getJson('/api/configuracionactas/77')->assertNotFound();
    }

    public function test_crea_la_fila_del_nivel_si_no_existe(): void
    {
        $this->putJson('/api/configuracionactas/4', ['idSecretario' => 103, 'idSupervisor' => 101])
            ->assertOk()
            ->assertJsonPath('configuracionActas.secretario.nombre', 'DIAZ PEREZ SARA')
            ->assertJsonPath('configuracionActas.supervisor.uid', 101);

        $this->assertSame(1, DB::table('configuracionActas')->where('idNivel', 4)->count());
    }

    public function test_actualiza_solo_los_campos_enviados_y_null_quita(): void
    {
        $this->putJson('/api/configuracionactas/5', ['idSupervisor' => 103])->assertOk();
        $fila = DB::table('configuracionActas')->where('idNivel', 5)->first();
        $this->assertSame([101, 103], [(int) $fila->idSecretario, (int) $fila->idSupervisor]);

        $this->putJson('/api/configuracionactas/5', ['idSecretario' => null])->assertOk()->assertJsonPath('configuracionActas.secretario.uid', null);
        $this->assertNull(DB::table('configuracionActas')->where('idNivel', 5)->value('idSecretario'));
        $this->assertSame(1, DB::table('configuracionActas')->where('idNivel', 5)->count());
    }

    public function test_ignora_el_presidente(): void
    {
        // Mandar idPresidente no cuenta como firmante ni se guarda
        $this->putJson('/api/configuracionactas/4', ['idPresidente' => 103])->assertStatus(400)
            ->assertJsonFragment(['message' => 'Indica al menos un firmante: idSecretario o idSupervisor']);
        $this->putJson('/api/configuracionactas/5', ['idPresidente' => 103, 'idSupervisor' => 103])->assertOk();
        $this->assertSame(102, (int) DB::table('configuracionActas')->where('idNivel', 5)->value('idPresidente'));
    }

    public function test_valida_personas_y_nivel(): void
    {
        $this->putJson('/api/configuracionactas/5', ['idSecretario' => 999])
            ->assertStatus(400)
            ->assertJsonFragment(['El secretario no existe en el catálogo de personas.']);
        $this->putJson('/api/configuracionactas/5', ['idSupervisor' => 'PEDRO SOTO'])->assertStatus(400);
        $this->putJson('/api/configuracionactas/5', [])->assertStatus(400);
        $this->putJson('/api/configuracionactas/77', ['idSecretario' => 101])->assertNotFound();

        $this->assertSame(101, (int) DB::table('configuracionActas')->where('idNivel', 5)->value('idSecretario'));
    }

    public function test_secretario_y_supervisor_deben_ser_personas_distintas(): void
    {
        $this->putJson('/api/configuracionactas/5', ['idSupervisor' => 101])
            ->assertStatus(400)
            ->assertJsonPath('error.idSupervisor.0', 'El supervisor no puede ser la misma persona que el secretario.');
        $this->putJson('/api/configuracionactas/4', ['idSecretario' => 103, 'idSupervisor' => 103])->assertStatus(400);
        $this->assertFalse(DB::table('configuracionActas')->where('idNivel', 4)->exists());

        // Intercambiarlos en la misma petición sí se permite
        $this->putJson('/api/configuracionactas/5', ['idSecretario' => 102, 'idSupervisor' => 101])->assertOk();
    }

    public function test_elimina_la_configuracion_del_nivel(): void
    {
        $this->deleteJson('/api/configuracionactas/5')->assertOk();
        $this->assertFalse(DB::table('configuracionActas')->where('idNivel', 5)->exists());
        $this->deleteJson('/api/configuracionactas/5')->assertNotFound();
        $this->deleteJson('/api/configuracionactas/77')->assertNotFound();
    }
}
