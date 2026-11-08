<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * calendarioPago tenia idTurno como llave primaria, lo que solo permitia un calendario por turno.
     * Se agrega idCalendarioPago como llave y idTurno queda como columna indexada.
     * Los registros existentes reciben un idCalendarioPago consecutivo.
     */
    public function up(): void
    {
        if (!Schema::hasTable('calendarioPago') || Schema::hasColumn('calendarioPago', 'idCalendarioPago')) {
            return;
        }

        Schema::table('calendarioPago', function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table('calendarioPago', function (Blueprint $table) {
            $table->increments('idCalendarioPago')->first();
            $table->index('idTurno');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Falla si ya existe mas de un calendario para el mismo turno.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('calendarioPago', 'idCalendarioPago')) {
            return;
        }

        Schema::table('calendarioPago', function (Blueprint $table) {
            $table->dropIndex(['idTurno']);
            $table->dropColumn('idCalendarioPago');
        });

        Schema::table('calendarioPago', function (Blueprint $table) {
            $table->primary('idTurno');
        });
    }
};
