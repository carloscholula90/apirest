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
        Schema::create('permisosPersona', function (Blueprint $table) {
            $table->integer('uid')->primary();
            $table->integer('secuencia')->nullable();
            $table->integer('idAplicacion')->nullable();
            $table->integer('idConsecutivo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisosPersona');
    }
};
