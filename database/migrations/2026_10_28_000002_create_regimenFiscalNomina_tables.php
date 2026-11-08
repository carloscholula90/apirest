<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Esquema de impuestos y retenciones para la hoja de pago (Notion, seccion 2).
     * Es independiente de RegimenesFiscales, que es el catalogo del SAT que usa facturacion.
     */
    public function up(): void
    {
        if (!Schema::hasTable('regimenFiscalNomina')) {
            Schema::create('regimenFiscalNomina', function (Blueprint $table) {
                $table->increments('idRegimenFiscal');
                $table->string('descripcion');
                $table->tinyInteger('activo')->default(1);
            });
        }

        // Costo por hora de cada regimen, con historico por vigencia
        if (!Schema::hasTable('costoHoraRegimen')) {
            Schema::create('costoHoraRegimen', function (Blueprint $table) {
                $table->increments('idCostoHora');
                $table->integer('idRegimenFiscal')->index();
                $table->decimal('costoHora', 10, 2);
                $table->date('vigenciaInicio');
                $table->date('vigenciaFin')->nullable();
            });
        }

        if (!Schema::hasTable('regimenFiscalImpuesto')) {
            Schema::create('regimenFiscalImpuesto', function (Blueprint $table) {
                $table->increments('idRegimenFiscalImpuesto');
                $table->integer('idRegimenFiscal');
                $table->integer('idImpuesto');
                $table->tinyInteger('activo')->default(1);
                $table->dateTime('fechaCreacion')->nullable();

                $table->unique(['idRegimenFiscal', 'idImpuesto']);
            });
        }

        // El catalogo de impuestos solo tenia la descripcion
        Schema::table('impuestos', function (Blueprint $table) {
            if (!Schema::hasColumn('impuestos', 'porcentaje')) {
                $table->decimal('porcentaje', 8, 4)->default(0);
            }
            if (!Schema::hasColumn('impuestos', 'esRetencion')) {
                $table->tinyInteger('esRetencion')->default(0);
            }
        });

        // Cada empleado debe tener asociado un regimen fiscal de nomina
        if (!Schema::hasColumn('empleado', 'idRegimenFiscalNomina')) {
            Schema::table('empleado', function (Blueprint $table) {
                $table->integer('idRegimenFiscalNomina')->nullable();
            });
        }

        // Indica que horas del horario del profesor se pagan (CLASE si, RECESO o NO DISPONIBLE no)
        if (!Schema::hasColumn('tipoBloque', 'pagable')) {
            Schema::table('tipoBloque', function (Blueprint $table) {
                $table->tinyInteger('pagable')->default(1);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tipoBloque', 'pagable')) {
            Schema::table('tipoBloque', function (Blueprint $table) {
                $table->dropColumn('pagable');
            });
        }

        if (Schema::hasColumn('empleado', 'idRegimenFiscalNomina')) {
            Schema::table('empleado', function (Blueprint $table) {
                $table->dropColumn('idRegimenFiscalNomina');
            });
        }

        Schema::table('impuestos', function (Blueprint $table) {
            if (Schema::hasColumn('impuestos', 'esRetencion')) {
                $table->dropColumn('esRetencion');
            }
            if (Schema::hasColumn('impuestos', 'porcentaje')) {
                $table->dropColumn('porcentaje');
            }
        });

        Schema::dropIfExists('regimenFiscalImpuesto');
        Schema::dropIfExists('costoHoraRegimen');
        Schema::dropIfExists('regimenFiscalNomina');
    }
};
