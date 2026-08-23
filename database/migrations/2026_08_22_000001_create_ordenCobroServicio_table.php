<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenCobroServicio', function (Blueprint $table) {
            $table->integer('idNivel');
            $table->integer('idServicio');
            $table->integer('orden');

            $table->primary(
                ['idNivel', 'idServicio'],
                'pk_ordenCobroServicio'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenCobroServicio');
    }
};
