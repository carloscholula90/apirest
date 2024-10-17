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
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->integer('indexCalif')->primary();
            $table->float('Parcial1')->nullable();
            $table->float('Parcial2')->nullable();
            $table->float('Parcial3')->nullable();
            $table->integer('Faltas1')->nullable();
            $table->integer('Faltas2')->nullable();
            $table->integer('Faltas3')->nullable();
            $table->float('CF')->nullable();
            $table->integer('idExamen')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
