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
        Schema::create('detasignatura', function (Blueprint $table) {
            $table->integer('idPlan');
            $table->integer('idCarrera');
            $table->string('idAsignatura');
            $table->integer('orden')->nullable();
            $table->integer('semestre')->nullable();

            $table->primary(['idPlan', 'idCarrera', 'idAsignatura']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detasignatura');
    }
};
