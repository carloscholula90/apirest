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
        Schema::table('rolesPersona_roles', function (Blueprint $table) {
            $table->foreign(['rolesPersona_idRol'], 'rolesPersona_roles_ibfk_1')->references(['idRol'])->on('rolesPersona')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['roles_idRol'], 'rolesPersona_roles_ibfk_2')->references(['idRol'])->on('roles')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rolesPersona_roles', function (Blueprint $table) {
            $table->dropForeign('rolesPersona_roles_ibfk_1');
            $table->dropForeign('rolesPersona_roles_ibfk_2');
        });
    }
};
