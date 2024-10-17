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
        Schema::create('empresasPersona', function (Blueprint $table) {
            $table->integer('uid');
            $table->string('rfc');
            $table->string('predeterminado')->nullable();

            $table->primary(['uid', 'rfc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresasPersona');
    }
};
