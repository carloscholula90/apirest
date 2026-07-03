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
        if (Schema::hasTable('tipoBloque')) {
            return;
        }

        Schema::create('tipoBloque', function (Blueprint $table) {
            $table->integer('idTipoBloque')->primary();
            $table->string('descripcion')->nullable();
            $table->string('color')->nullable();
            $table->tinyInteger('activo')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipoBloque');
    }
};
