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
        Schema::create('permisosRol', function (Blueprint $table) {
            $table->integer('idAplicacion')->index('fk1_aplicaciones');
            $table->integer('idRol')->default(0);

            $table->primary(['idRol', 'idAplicacion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisosRol');
    }
};
