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
        Schema::create('grupos', function (Blueprint $table) {
            $table->integer('idNivel');
            $table->integer('idPeriodo');
            $table->string('idAsignatura');
            $table->string('grupo');
            $table->integer('uidProfesor')->nullable();
            $table->integer('secuenciaProfesor')->nullable();
            $table->integer('inscritos')->nullable();
            $table->integer('capacidad')->nullable();
            $table->integer('idIdioma')->nullable();

            $table->primary(['idNivel', 'idPeriodo', 'idAsignatura', 'grupo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
