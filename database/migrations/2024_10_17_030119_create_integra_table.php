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
            $table->unsignedInteger('uid');
            $table->integer('secuencia');
            $table->integer('idRol');
            $table->boolean('activo')->nullable();

            $table->primary(['uid', 'secuencia']);
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
