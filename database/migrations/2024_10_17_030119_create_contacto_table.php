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
        Schema::create('contacto', function (Blueprint $table) {
            $table->comment('Telefonos y correos donde puedes ser localizado');
            $table->integer('uid');
            $table->integer('idParentesco');
            $table->integer('idTipoContacto');
            $table->integer('consecutivo');
            $table->string('dato')->nullable();

            $table->primary(['uid', 'idParentesco', 'idTipoContacto', 'consecutivo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacto');
    }
};
