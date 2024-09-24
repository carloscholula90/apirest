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
        Schema::table('salud', function (Blueprint $table) {
            $table->foreign(['uid'], 'salud_ibfk_1')->references(['uid'])->on('persona')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salud', function (Blueprint $table) {
            $table->dropForeign('salud_ibfk_1');
        });
    }
};
