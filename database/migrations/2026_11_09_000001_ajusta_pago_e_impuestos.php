<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 2026_10_28_000003 solo crea pago si no existe, pero en la base ya habia una tabla pago anterior
     * (llave idTurno + fechaInicio + uid) que el procesamiento no puede llenar. Si esa tabla esta vacia
     * se reemplaza por la estructura nueva; si tiene datos se detiene para no perderlos.
     *
     * 2026_10_28_000002 agrega impuestos.porcentaje en 0, pero el porcentaje real esta en valor.
     */
    public function up(): void
    {
        if (Schema::hasTable('pago') && !Schema::hasColumn('pago', 'idPago')) {
            if (DB::table('pago')->exists()) {
                throw new RuntimeException('La tabla pago tiene datos con la estructura anterior; migralos a mano antes de continuar.');
            }

            Schema::drop('pago');

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

        if (Schema::hasColumn('impuestos', 'porcentaje') && Schema::hasColumn('impuestos', 'valor')) {
            DB::table('impuestos')
                ->where('isPorcentaje', 1)
                ->where('porcentaje', 0)
                ->update(['porcentaje' => DB::raw('valor')]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Regresa pago a la estructura anterior solo si esta vacia; el porcentaje copiado se conserva.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('pago', 'idPago') || DB::table('pago')->exists()) {
            return;
        }

        Schema::drop('pago');

        Schema::create('pago', function (Blueprint $table) {
            $table->integer('idTurno');
            $table->date('fechaInicio');
            $table->integer('uid');
            $table->integer('secuencia');
            $table->decimal('costoPorHora', 10, 2)->default(0);
            $table->decimal('totalHoras', 10, 2)->default(0);
            $table->decimal('horasADescontar', 10, 2)->default(0);
            $table->decimal('netoAPagar', 12, 2)->default(0);
            $table->dateTime('fechaCreacion')->useCurrent();

            $table->primary(['idTurno', 'fechaInicio', 'uid']);
        });
    }
};
