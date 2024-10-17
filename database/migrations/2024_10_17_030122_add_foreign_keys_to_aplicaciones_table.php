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
        Schema::table('aplicaciones', function (Blueprint $table) {
            $table->foreign(['idModulo'], 'aplicacionesFK1')->references(['idModulo'])->on('modulos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aplicaciones', function (Blueprint $table) {
            $table->dropForeign('aplicacionesFK1');
        });
    }
};
