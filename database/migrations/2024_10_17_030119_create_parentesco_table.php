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
        Schema::create('parentesco', function (Blueprint $table) {
            $table->comment('parentesco de las personas');
            $table->integer('idParentesco')->primary();
            $table->string('descripcion')->unique('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parentesco');
    }
};
