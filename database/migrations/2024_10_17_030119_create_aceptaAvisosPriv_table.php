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
        Schema::create('aceptaAvisosPriv', function (Blueprint $table) {
            $table->integer('idAviso');
            $table->unsignedInteger('uid')->index('aceptaavisospriv_ibfk_2');
            $table->dateTime('fechaAcepta')->nullable();
            $table->string('ip', 20)->nullable();

            $table->primary(['idAviso', 'uid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aceptaAvisosPriv');
    }
};
