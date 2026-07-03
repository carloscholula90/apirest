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
        if (Schema::hasTable('calendarioPago')) {
            return;
        }

        Schema::create('calendarioPago', function (Blueprint $table) {
            $table->integer('idTurno')->primary();
            $table->date('fechaInicio')->nullable();
            $table->date('fechaFin')->nullable();
            $table->tinyInteger('procesado')->default(0);
            $table->dateTime('fechaCreacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendarioPago');
    }
};
