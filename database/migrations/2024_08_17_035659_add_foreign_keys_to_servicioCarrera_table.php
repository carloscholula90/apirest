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
        Schema::table('servicioCarrera', function (Blueprint $table) {
            $table->foreign(['idServicio'], 'servicioCarrera_ibfk_1')->references(['idServicio'])->on('servicio')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicioCarrera', function (Blueprint $table) {
            $table->dropForeign('servicioCarrera_ibfk_1');
        });
    }
};
