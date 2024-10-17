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
        Schema::create('servicioCarrera', function (Blueprint $table) {
            $table->integer('idServicio');
            $table->integer('idCarrera');
            $table->integer('idPlan');
            $table->integer('parcialidades')->nullable();
            $table->integer('semestre')->nullable()->comment('0 Carga Todos');
            $table->float('monto')->nullable()->comment('monto por carrera');

            $table->primary(['idServicio', 'idCarrera', 'idPlan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicioCarrera');
    }
};
