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
        Schema::create('direcciones', function (Blueprint $table) {
            $table->comment('Direcciones donde puedes ser localizado');
            $table->integer('uid');
            $table->integer('idParentesco');
            $table->integer('idTipoDireccion');
            $table->integer('consecutivo');
            $table->integer('idPais')->nullable();
            $table->integer('idEstado')->nullable();
            $table->integer('idCiudad')->nullable();
            $table->integer('idCp')->nullable();
            $table->string('noExterior')->nullable();
            $table->string('noInterior')->nullable();

            $table->primary(['uid', 'idParentesco', 'idTipoDireccion', 'consecutivo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};
