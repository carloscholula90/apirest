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
        Schema::create('aspirante', function (Blueprint $table) {
            $table->integer('folio')->primary();
            $table->integer('idPeriodo')->nullable();
            $table->string('curpAspirante')->nullable();
            $table->integer('idCarrera')->nullable();
            $table->integer('idEscolaridad')->nullable();
            $table->boolean('adeudoAsignaturas')->nullable()->comment('adeuda asignaturas');
            $table->integer('idMedio')->nullable();
            $table->boolean('publica')->nullable()->comment('escuela procedencia');
            $table->integer('paisCursoGradoAnterior')->nullable()->comment('pais donde se curso el grado anterior');
            $table->integer('estadoCursoGradoAnterior')->nullable()->comment('estado donde se curso el grado anterior');
            $table->integer('secuencia')->nullable();
            $table->integer('curpEmpleado')->nullable();
            $table->dateTime('fechaSolicitud')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aspirante');
    }
};
