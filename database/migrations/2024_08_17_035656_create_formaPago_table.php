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
        Schema::create('formaPago', function (Blueprint $table) {
            $table->integer('idFormaPago')->primary();
            $table->string('descripcion')->nullable();
            $table->boolean('solicita4digitos')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formaPago');
    }
};
