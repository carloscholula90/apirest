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
        Schema::create('familia', function (Blueprint $table) {
            $table->integer('uid');
            $table->string('idParentesco');
            $table->boolean('tutor')->nullable();
            $table->string('nombre')->nullable();
            $table->string('primerApellido')->nullable();
            $table->string('segundoApellido')->nullable();
            $table->timestamp('fechaNacimiento')->useCurrentOnUpdate()->useCurrent();
            $table->boolean('finado')->nullable();

            $table->primary(['uid', 'idParentesco']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('familia');
    }
};
