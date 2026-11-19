<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Alta y edición de turnos: descripción y letra sin repetirse, parciales mayor a 0 (SQLite en memoria)
class TurnoApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'turnos_test',
            'database.connections.turnos_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('turnos_test');

        Schema::create('turno', function (Blueprint $table) {
            $table->integer('idTurno')->primary();
            $table->string('descripcion', 20)->nullable()->unique();
            $table->string('letra', 1)->nullable();
            $table->integer('parciales')->nullable();
        });

        // Como en la base: TODOS sin letra y descripciones con \r al final
        DB::table('turno')->insert([
            ['idTurno' => 0, 'descripcion' => 'TODOS', 'letra' => null, 'parciales' => null],
            ['idTurno' => 1, 'descripcion' => 'MATUTINO', 'letra' => 'M', 'parciales' => 3],
            ['idTurno' => 2, 'descripcion' => "VESPERTINO\r", 'letra' => 'V', 'parciales' => 3],
        ]);
    }

    private function alta(array $datos)
    {
        return $this->postJson('/api/turnos/create', array_merge(['descripcion' => 'nocturno', 'letra' => 'n', 'parciales' => 2], $datos));
    }

    public function test_alta_valida_guarda_en_mayusculas(): void
    {
        $this->alta([])->assertOk();
        $turno = DB::table('turno')->where('idTurno', 3)->first();
        $this->assertSame(['NOCTURNO', 'N', 2], [$turno->descripcion, $turno->letra, (int) $turno->parciales]);
    }

    public function test_alta_no_repite_descripcion_ni_letra(): void
    {
        // Sin importar mayúsculas, espacios ni el \r guardado
        $this->alta(['descripcion' => ' vespertino '])->assertStatus(400)
            ->assertJsonPath('error.descripcion.0', 'Ya existe un turno con la descripción VESPERTINO (VESPERTINO, clave 2).');
        $this->alta(['letra' => 'm'])->assertStatus(400)
            ->assertJsonPath('error.letra.0', 'Ya existe un turno con la letra M (MATUTINO, clave 1).');
        $this->alta(['descripcion' => 'todos'])->assertStatus(400);
        $this->assertSame(3, DB::table('turno')->count());
    }

    public function test_alta_valida_parciales_y_formato(): void
    {
        foreach ([[0, 'El número de parciales debe ser mayor a 0.'], [-1, 'El número de parciales debe ser mayor a 0.'], ['dos', 'El número de parciales debe ser un número entero.']] as [$valor, $mensaje]) {
            $this->alta(['parciales' => $valor])->assertStatus(400)->assertJsonPath('error.parciales.0', $mensaje);
        }
        $this->postJson('/api/turnos/create', ['descripcion' => 'NOCTURNO', 'letra' => 'N'])->assertStatus(400)
            ->assertJsonPath('error.parciales.0', 'El número de parciales es obligatorio.');
        $this->alta(['letra' => 'NO'])->assertStatus(400)->assertJsonPath('error.letra.0', 'La letra del turno debe ser una sola letra.');
        $this->alta(['letra' => '1'])->assertStatus(400)->assertJsonPath('error.letra.0', 'La letra del turno debe ser una letra.');
        $this->alta(['letra' => null])->assertStatus(400)->assertJsonPath('error.letra.0', 'La letra del turno es obligatoria.');
        $this->alta(['descripcion' => str_repeat('A', 21)])->assertStatus(400);
        $this->assertSame(3, DB::table('turno')->count());
    }

    public function test_actualizar_valida_contra_los_demas_turnos(): void
    {
        // Conservar su propia descripción y letra no cuenta como repetido
        $this->putJson('/api/turnos/1', ['descripcion' => 'Matutino', 'letra' => 'm', 'parciales' => 4])->assertOk();
        $this->assertSame(4, (int) DB::table('turno')->where('idTurno', 1)->value('parciales'));

        $this->putJson('/api/turnos/1', ['descripcion' => 'VESPERTINO', 'letra' => 'M', 'parciales' => 3])->assertStatus(400)
            ->assertJsonValidationErrors(['descripcion'], 'error');
        $this->putJson('/api/turnos/1', ['descripcion' => 'MATUTINO', 'letra' => 'V', 'parciales' => 3])->assertStatus(400)
            ->assertJsonPath('error.letra.0', 'Ya existe un turno con la letra V (VESPERTINO, clave 2).');
        $this->putJson('/api/turnos/1', ['descripcion' => 'MATUTINO', 'letra' => 'M', 'parciales' => 0])->assertStatus(400);
        $this->putJson('/api/turnos/99', ['descripcion' => 'X', 'letra' => 'X', 'parciales' => 1])->assertNotFound();

        // La clave no cambia aunque se mande otra
        $this->putJson('/api/turnos/1', ['idTurno' => 50, 'descripcion' => 'MATUTINO', 'letra' => 'M', 'parciales' => 3])->assertOk();
        $this->assertTrue(DB::table('turno')->where('idTurno', 1)->exists());
    }

    public function test_actualizacion_parcial_solo_revisa_lo_que_llega(): void
    {
        $this->patchJson('/api/turnos/2', ['parciales' => 2])->assertOk();
        $this->assertSame(2, (int) DB::table('turno')->where('idTurno', 2)->value('parciales'));

        $this->patchJson('/api/turnos/2', ['letra' => 'M'])->assertStatus(400)->assertJsonPath('error.letra.0', 'Ya existe un turno con la letra M (MATUTINO, clave 1).');
        $this->patchJson('/api/turnos/2', ['parciales' => 0])->assertStatus(400);
        $this->patchJson('/api/turnos/2', ['descripcion' => 'vespertino'])->assertOk();
        $this->assertSame('VESPERTINO', DB::table('turno')->where('idTurno', 2)->value('descripcion'));
    }
}
