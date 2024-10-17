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
        Schema::create('inscripcion', function (Blueprint $table) {
            $table->integer('idPeriodo');
            $table->integer('idPersona');
            $table->string('curp');
            $table->integer('idCarrera');
            $table->integer('idPlan');
            $table->string('grupo')->nullable();
            $table->integer('semestre')->nullable();
            $table->integer('idPeriodoReal')->nullable();

            $table->primary(['idPeriodo', 'idPersona', 'curp', 'idCarrera', 'idPlan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripcion');
    }
};
