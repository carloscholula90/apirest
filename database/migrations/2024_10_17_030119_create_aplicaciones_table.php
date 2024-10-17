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
        Schema::create('aplicaciones', function (Blueprint $table) {
            $table->integer('idAplicacion')->primary();
            $table->string('descripcion', 90)->nullable();
            $table->integer('activo')->nullable();
            $table->integer('idModulo')->index('aplicacionesfk1');
            $table->string('alias', 150)->nullable();
            $table->string('icono', 150)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aplicaciones');
    }
};
