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

        // La llave foranea a turno usa la llave primaria como indice; necesita el suyo antes de quitarla
        Schema::table('calendarioPago', function (Blueprint $table) {
            $table->index('idTurno');
        });

        Schema::table('calendarioPago', function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table('calendarioPago', function (Blueprint $table) {
            $table->increments('idCalendarioPago')->first();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Regresa la llave original (idTurno, fechaInicio); falla si hay dos calendarios del mismo turno y fecha.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('calendarioPago', 'idCalendarioPago')) {
            return;
        }

        Schema::table('calendarioPago', function (Blueprint $table) {
            $table->dropColumn('idCalendarioPago');
        });

        // Primero la llave primaria, para que la llave foranea a turno no se quede sin indice
        Schema::table('calendarioPago', function (Blueprint $table) {
            $table->primary(['idTurno', 'fechaInicio']);
        });

        Schema::table('calendarioPago', function (Blueprint $table) {
            $table->dropIndex(['idTurno']);
        });
    }
};
