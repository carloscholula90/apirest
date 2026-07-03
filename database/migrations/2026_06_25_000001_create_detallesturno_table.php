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
        Schema::create('detallesturno', function (Blueprint $table) {
            $table->integer('idDtlTurno')->primary();
            $table->integer('idTurno');
            $table->string('diaSemana')->nullable();
            $table->time('horaInicio')->nullable();
            $table->time('horaFin')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detallesturno');
    }
};
