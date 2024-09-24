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
        Schema::create('alergia', function (Blueprint $table) {
            $table->integer('uid');
            $table->integer('consecutivo');
            $table->text('alergia');

            $table->primary(['uid', 'consecutivo']);
        });

        $table->foreign('uid')->references('uid')->on('persona')->onDelete('cascade');
   
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alergia');
    }
};
