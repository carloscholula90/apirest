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
        Schema::create('plan', function (Blueprint $table) {
            $table->integer('idPlan');
            $table->integer('idCarrera');
            $table->string('descripcion')->nullable();
            $table->string('rvoe')->nullable();
            $table->timestamp('fechainicio')->useCurrentOnUpdate()->useCurrent();
            $table->integer('idNivel')->nullable();
            $table->integer('idModalidad')->nullable();
            $table->integer('semestres')->nullable();
            $table->boolean('vigente')->nullable();
            $table->boolean('estatal')->nullable();
            $table->integer('decimales')->nullable();

            $table->primary(['idPlan', 'idCarrera']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan');
    }
};
