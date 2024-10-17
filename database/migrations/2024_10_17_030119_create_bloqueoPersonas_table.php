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
        Schema::create('bloqueoPersonas', function (Blueprint $table) {
            $table->integer('uid');
            $table->integer('secuencia')->index('secuencia');
            $table->integer('idBloqueo')->index('idbloqueo');
            $table->integer('uidBloqueador')->nullable();
            $table->integer('secuenciaBloq')->nullable();
            $table->string('BloqueoActivo', 1)->nullable();
            $table->date('fechaBloqueo')->nullable();

            $table->primary(['uid', 'secuencia', 'idBloqueo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloqueoPersonas');
    }
};
