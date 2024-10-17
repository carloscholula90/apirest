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
        Schema::create('usuariosPerfil', function (Blueprint $table) {
            $table->unsignedInteger('uid');
            $table->integer('secuencia');
            $table->integer('idPerfil')->index('usuariosperfilfk2');

            $table->primary(['uid', 'secuencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuariosPerfil');
    }
};
