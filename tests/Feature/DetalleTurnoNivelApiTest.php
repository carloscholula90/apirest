<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Días del turno (dtlTurno) por nivel, en SQLite en memoria con las columnas reales
class DetalleTurnoNivelApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'turno_test',
            'database.connections.turno_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('turno_test');

        Schema::create('nivel', function (Blueprint $table) {
            $table->integer('idNivel');
            $table->string('descripcion');
        });
        Schema::create('dtlTurno', function (Blueprint $table) {
            $table->increments('idDtlTurno');
            $table->integer('idTurno');
            $table->string('diaSemana');
            $table->time('horaInicio');
            $table->time('horaFin');
            $table->integer('idNivel')->nullable();
        });

        DB::table('nivel')->insert([['idNivel' => 4, 'descripcion' => 'BACHILLERATO'], ['idNivel' => 5, 'descripcion' => 'LICENCIATURA']]);
        DB::table('dtlTurno')->insert(['idDtlTurno' => 1, 'idTurno' => 1, 'diaSemana' => '2', 'horaInicio' => '08:00:00', 'horaFin' => '13:30:00', 'idNivel' => 5]);
    }

    private function alta(array $datos)
    {
        return $this->postJson('/api/detallesturno/create', array_merge(['idTurno' => 1, 'diaSemana' => 2, 'horaInicio' => '07:00', 'horaFin' => '14:00'], $datos));
    }

    public function test_el_mismo_dia_se_puede_dar_de_alta_en_otro_nivel(): void
    {
        $this->alta(['idNivel' => 4])->assertOk()->assertJsonPath('detalleTurno.idNivel', 4);
        $this->alta(['idNivel' => 4])->assertStatus(400)->assertJsonFragment(['message' => 'El dia ya tiene horario en este turno para el nivel, no se permite el empalme de dias']);
        $this->alta(['idNivel' => 5])->assertStatus(400);

        // Un día general (sin nivel) convive con los de cada nivel, pero solo hay uno general por día
        $this->alta([])->assertOk()->assertJsonPath('detalleTurno.idNivel', null);
        $this->alta([])->assertStatus(400)->assertJsonFragment(['message' => 'El dia ya tiene horario en este turno, no se permite el empalme de dias']);

        $this->alta(['idNivel' => 99])->assertStatus(400);
        $this->assertSame(3, DB::table('dtlTurno')->where('idTurno', 1)->where('diaSemana', '2')->count());
    }

    public function test_consulta_por_nivel_con_respaldo_general(): void
    {
        DB::table('dtlTurno')->insert(['idTurno' => 1, 'diaSemana' => '3', 'horaInicio' => '07:00:00', 'horaFin' => '14:00:00', 'idNivel' => null]);

        $this->assertSame([5], array_column($this->getJson('/api/detallesturno/1?idNivel=5')->assertOk()->json('detallesTurno'), 'idNivel'));
        // Bachillerato no tiene propios: el general
        $this->assertSame(['3'], array_column($this->getJson('/api/detallesturno/1?idNivel=4')->json('detallesTurno'), 'diaSemana'));
        // Sin filtro: todos
        $this->assertCount(2, $this->getJson('/api/detallesturno/1')->json('detallesTurno'));
    }

    public function test_editar_conserva_el_nivel_si_no_llega_y_valida_empalme_en_el_nivel(): void
    {
        $this->alta(['idNivel' => 4, 'diaSemana' => 3])->assertOk();
        $id = DB::table('dtlTurno')->where('idNivel', 4)->value('idDtlTurno');

        // Sin idNivel se conserva el 4
        $this->putJson('/api/detallesturno/'.$id, ['idTurno' => 1, 'diaSemana' => 3, 'horaInicio' => '08:00', 'horaFin' => '12:00'])->assertOk();
        $this->assertSame(4, (int) DB::table('dtlTurno')->where('idDtlTurno', $id)->value('idNivel'));

        // Moverlo a lunes de licenciatura choca con el que ya existe
        $this->putJson('/api/detallesturno/'.$id, ['idTurno' => 1, 'diaSemana' => 2, 'horaInicio' => '08:00', 'horaFin' => '12:00', 'idNivel' => 5])->assertStatus(400);
    }
}
