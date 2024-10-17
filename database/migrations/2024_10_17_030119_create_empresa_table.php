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
        Schema::create('empresa', function (Blueprint $table) {
            $table->integer('rfc')->primary();
            $table->string('razonSocial')->nullable();
            $table->integer('idRegimenFiscal')->nullable();
            $table->string('tipoPersona')->nullable()->comment('M-Moral/F-Fisica');
            $table->string('calle')->nullable();
            $table->string('noExterior')->nullable();
            $table->string('noInterior')->nullable();
            $table->integer('idPais')->nullable();
            $table->integer('idEstado')->nullable();
            $table->integer('idCiudad')->nullable();
            $table->integer('idCP')->nullable();
            $table->integer('idAsentamiento')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa');
    }
};
