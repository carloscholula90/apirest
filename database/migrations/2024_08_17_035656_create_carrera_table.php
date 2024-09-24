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
        Schema::create('carrera', function (Blueprint $table) {
            $table->integer('idCarrera')->primary();
            $table->string('descripcion')->nullable();
            $table->string('letra')->nullable();
            $table->integer('diaInicioCargo')->nullable()->comment('dia del mes que inicia el cargo');
            $table->integer('diaInicioRecargo')->nullable()->comment('dia del mes que inicia el recargo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrera');
    }
};
