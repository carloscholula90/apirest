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
        Schema::create('encabezadoFactura', function (Blueprint $table) {
            $table->string('serie');
            $table->integer('folio');
            $table->string('rfc')->nullable();
            $table->dateTime('fechafactura')->nullable();
            $table->string('uuid')->nullable()->comment('sello/llave SAT');
            $table->string('idUsoCFDI')->nullable();
            $table->string('idFormaPago')->nullable();
            $table->integer('idstatusFactura')->nullable()->comment('procesada,cancelada,eliminada');

            $table->primary(['serie', 'folio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encabezadoFactura');
    }
};
