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
        Schema::table('estado', function (Blueprint $table) {
            $table->foreign(['idPais'], 'estado_ibfk_1')->references(['idPais'])->on('pais')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estado', function (Blueprint $table) {
            $table->dropForeign('estado_ibfk_1');
        });
    }
};
