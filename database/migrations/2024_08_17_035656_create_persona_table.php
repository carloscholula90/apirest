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
        Schema::create('persona', function (Blueprint $table) {
            $table->integer('uid', true);
            $table->string('curp')->nullable()->unique('hey');
            $table->string('nombre')->nullable();
            $table->string('primerApellido')->nullable();
            $table->string('segundoApellido')->nullable();
            $table->time('fechaNacimiento')->nullable();
            $table->char('sexo', 1)->nullable();
            $table->integer('idPais')->nullable()->comment('pais de nacimiento');
            $table->integer('idEstado')->nullable()->comment('estado de nacimiento');
            $table->integer('idCiudad')->nullable()->comment('ciudad de nacimiento');
            $table->integer('idEdoCivil')->nullable();
            $table->string('rfc')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persona');
    }
};
