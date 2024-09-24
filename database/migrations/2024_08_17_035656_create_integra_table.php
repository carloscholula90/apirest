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
        Schema::create('integra', function (Blueprint $table) {
            $table->integer('secuencia');
            $table->string('curp');
            $table->integer('uid')->nullable()->index('uid');
            $table->integer('idRol')->nullable();
            $table->boolean('activo')->nullable();
            $table->time('fechainicio')->nullable();
            $table->time('fechabaja')->nullable();

            $table->primary(['secuencia', 'curp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integra');
    }
};
