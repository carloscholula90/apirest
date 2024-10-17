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
        Schema::create('codigoPostal', function (Blueprint $table) {
            $table->integer('idPais');
            $table->integer('idEstado');
            $table->integer('idCiudad');
            $table->integer('idCp');
            $table->integer('cp')->nullable();
            $table->string('descripcion')->nullable();
            $table->integer('idAsentamiento')->nullable();

            $table->primary(['idPais', 'idEstado', 'idCiudad', 'idCp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigoPostal');
    }
};
