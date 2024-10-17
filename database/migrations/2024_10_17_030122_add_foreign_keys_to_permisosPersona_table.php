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
        Schema::table('permisosPersona', function (Blueprint $table) {
            $table->foreign(['uid', 'secuencia'], 'permisosPersonaFK1')->references(['uid', 'secuencia'])->on('integra')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['idAplicacion'], 'permisosPersonaFK2')->references(['idAplicacion'])->on('aplicaciones')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permisosPersona', function (Blueprint $table) {
            $table->dropForeign('permisosPersonaFK1');
            $table->dropForeign('permisosPersonaFK2');
        });
    }
};
