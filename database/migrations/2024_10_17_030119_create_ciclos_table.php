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
        Schema::create('ciclos', function (Blueprint $table) {
            $table->integer('idNivel');
            $table->integer('idPeriodo');
            $table->integer('secuencia');
            $table->integer('uid');
            $table->integer('idCarrera');
            $table->integer('idPlan');
            $table->string('grupo')->nullable();
            $table->integer('semestre')->nullable();
            $table->integer('idPeriodoReal')->nullable();
            $table->integer('indexCalif')->nullable();

            $table->primary(['idNivel', 'idPeriodo', 'secuencia', 'uid', 'idCarrera', 'idPlan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciclos');
    }
};
