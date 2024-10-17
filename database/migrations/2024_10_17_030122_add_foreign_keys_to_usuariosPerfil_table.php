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
        Schema::table('usuariosPerfil', function (Blueprint $table) {
            $table->foreign(['uid', 'secuencia'], 'usuariosPerfilFK1')->references(['uid', 'secuencia'])->on('integra')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['idPerfil'], 'usuariosPerfilFK2')->references(['idPerfil'])->on('perfil')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuariosPerfil', function (Blueprint $table) {
            $table->dropForeign('usuariosPerfilFK1');
            $table->dropForeign('usuariosPerfilFK2');
        });
    }
};
