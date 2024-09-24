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
        Schema::create('edocta', function (Blueprint $table) {
            $table->integer('secuencia');
            $table->integer('uid');
            $table->integer('idServicio');
            $table->integer('consecutivo');
            $table->float('cargo')->nullable();
            $table->float('abono')->nullable();
            $table->date('fechaMovto')->nullable();
            $table->string('referencia')->nullable();
            $table->integer('idformaPago')->nullable();
            $table->string('cuatrodigitos')->nullable();

            $table->primary(['secuencia', 'uid', 'idServicio', 'consecutivo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edocta');
    }
};
