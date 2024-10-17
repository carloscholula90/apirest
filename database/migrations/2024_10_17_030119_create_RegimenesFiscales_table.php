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
        Schema::create('RegimenesFiscales', function (Blueprint $table) {
            $table->integer('idRegimenFiscal')->primary();
            $table->string('fisica')->nullable();
            $table->string('moral')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('RegimenesFiscales');
    }
};
