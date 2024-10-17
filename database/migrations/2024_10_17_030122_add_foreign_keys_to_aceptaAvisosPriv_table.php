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
        Schema::table('aceptaAvisosPriv', function (Blueprint $table) {
            $table->foreign(['idAviso'], 'aceptaAvisosPriv_ibfk_1')->references(['idAviso'])->on('avisosPrivacidad')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['uid'], 'aceptaAvisosPriv_ibfk_2')->references(['uid'])->on('persona')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aceptaAvisosPriv', function (Blueprint $table) {
            $table->dropForeign('aceptaAvisosPriv_ibfk_1');
            $table->dropForeign('aceptaAvisosPriv_ibfk_2');
        });
    }
};
