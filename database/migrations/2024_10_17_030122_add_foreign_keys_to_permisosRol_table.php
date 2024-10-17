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
        Schema::table('permisosRol', function (Blueprint $table) {
            $table->foreign(['idAplicacion'], 'fk1_aplicaciones')->references(['idAplicacion'])->on('aplicaciones')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['idRol'], 'fk1_rolpersona')->references(['idRol'])->on('rol')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permisosRol', function (Blueprint $table) {
            $table->dropForeign('fk1_aplicaciones');
            $table->dropForeign('fk1_rolpersona');
        });
    }
};
