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
        Schema::create('periodo', function (Blueprint $table) {
            $table->integer('idNivel');
            $table->integer('idPeriodo');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->nullable();
            $table->boolean('inscripciones')->nullable();
            $table->date('fechaInicio')->nullable();
            $table->date('fechaTermino')->nullable();
            $table->integer('inmediato')->nullable()->comment('arranca el cargo inmediato');

            $table->primary(['idNivel', 'idPeriodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodo');
    }
};
