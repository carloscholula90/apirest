<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pago por empleado en un calendario y el detalle de impuestos aplicados (Notion, seccion 3).
     * idRegimenFiscal, costoPorHora y los porcentajes del detalle se guardan como foto del momento
     * en que se proceso, para que un cambio posterior en la configuracion no altere pagos cerrados.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pago')) {
            Schema::create('pago', function (Blueprint $table) {
                $table->increments('idPago');
                $table->integer('idCalendarioPago')->index();
                $table->integer('uid');
                $table->integer('idRegimenFiscal')->nullable();
                $table->decimal('costoPorHora', 10, 2);
                $table->decimal('totalHoras', 10, 2);
                $table->decimal('horasADescontar', 10, 2)->default(0);
                $table->decimal('netoAPagar', 12, 2);
                $table->dateTime('fechaCreacion')->nullable();

                $table->unique(['idCalendarioPago', 'uid']);
            });
        }

        if (!Schema::hasTable('detallePago')) {
            Schema::create('detallePago', function (Blueprint $table) {
                $table->increments('idDetallePago');
                $table->integer('idPago')->index();
                $table->integer('idRegimenFiscalImpuesto')->nullable();
                $table->integer('idImpuesto')->nullable();
                $table->string('descripcion');
                $table->decimal('porcentaje', 8, 4)->default(0);
                $table->tinyInteger('esRetencion')->default(0);
                $table->decimal('importe', 12, 2);
                $table->dateTime('fechaCreacion')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detallePago');
        Schema::dropIfExists('pago');
    }
};
