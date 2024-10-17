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
        Schema::create('detalleFactura', function (Blueprint $table) {
            $table->string('serie');
            $table->integer('folio');
            $table->integer('secuencia');
            $table->date('fecharegistro')->nullable();
            $table->string('descripcion')->nullable();
            $table->integer('cantidad')->nullable();
            $table->string('idProductoServicio')->nullable()->index('idproductoservicio');
            $table->float('costo')->nullable();
            $table->float('subtotal')->nullable();
            $table->string('idImpuesto')->nullable();

            $table->primary(['serie', 'folio', 'secuencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalleFactura');
    }
};
