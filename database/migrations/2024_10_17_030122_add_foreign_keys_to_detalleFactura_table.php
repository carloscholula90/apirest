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
        Schema::table('detalleFactura', function (Blueprint $table) {
            $table->foreign(['serie'], 'detalleFactura_ibfk_1')->references(['serie'])->on('encabezadoFactura')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['idProductoServicio'], 'detalleFactura_ibfk_2')->references(['idProductoServicio'])->on('productoServicioSAT')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalleFactura', function (Blueprint $table) {
            $table->dropForeign('detalleFactura_ibfk_1');
            $table->dropForeign('detalleFactura_ibfk_2');
        });
    }
};
