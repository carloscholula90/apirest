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
        Schema::create('servicio', function (Blueprint $table) {
            $table->integer('idServicio')->primary();
            $table->string('descripcion')->nullable();
            $table->float('monto')->nullable()->comment('para los montos en general');
            $table->boolean('partidaDoble')->nullable();
            $table->string('tipoServicio')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicio');
    }
};
