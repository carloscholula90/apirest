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
        Schema::create('avisosPrivacidad', function (Blueprint $table) {
            $table->integer('idAviso')->primary();
            $table->string('descripcion')->nullable();
            $table->string('activo')->nullable();
            $table->string('archivo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avisosPrivacidad');
    }
};
