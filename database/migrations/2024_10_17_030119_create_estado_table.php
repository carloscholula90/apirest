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
        Schema::create('estado', function (Blueprint $table) {
            $table->integer('idPais');
            $table->integer('idEstado');
            $table->string('descripcion')->nullable();

            $table->primary(['idPais', 'idEstado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estado');
    }
};
