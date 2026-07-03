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
        if (Schema::hasTable('horarioProfesor')) {
            return;
        }

        Schema::create('horarioProfesor', function (Blueprint $table) {
            $table->integer('uid');
            $table->integer('secuencia');
            $table->date('fechaInicio')->nullable();
            $table->date('fechaFin')->nullable();
            $table->string('diaSemana')->nullable();
            $table->time('horaInicio')->nullable();
            $table->time('horaFin')->nullable();
            $table->integer('idTipoBloque');

            $table->primary(['uid', 'secuencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarioProfesor');
    }
};
