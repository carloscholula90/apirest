<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('defaultTurno')) {
            return;
        }

        Schema::create('defaultTurno', function (Blueprint $table) {
            $table->integer('idTurno');
            $table->time('horaInicio')->nullable();
            $table->integer('idTipoBloque');
            $table->time('horaFin')->nullable();
            $table->tinyInteger('activo')->default(1);

            $table->primary(['idTurno', 'idTipoBloque']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defaultTurno');
    }
};
