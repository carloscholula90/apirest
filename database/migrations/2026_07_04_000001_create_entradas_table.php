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
        if (Schema::hasTable('entradas')) {
            return;
        }

        Schema::create('entradas', function (Blueprint $table) {
            $table->integer('id_entrada')->primary();
            $table->string('nombre')->nullable();
            $table->string('ip')->nullable();
            $table->integer('estatus')->default(1);
            $table->dateTime('fechaAlta')->nullable();
            $table->dateTime('fechaModificacion')->nullable();
            $table->string('contrasena')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas');
    }
};
