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
        Schema::table('alergia', function (Blueprint $table) {
            $table->foreign(['uid'], 'alergia_ibfk_1')->references(['uid'])->on('persona')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alergia', function (Blueprint $table) {
            $table->dropForeign('alergia_ibfk_1');
        });
    }
};