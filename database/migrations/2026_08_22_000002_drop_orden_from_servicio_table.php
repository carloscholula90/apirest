<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('servicio', 'orden')) {
            Schema::table('servicio', function (Blueprint $table) {
                $table->dropColumn('orden');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('servicio', 'orden')) {
            Schema::table('servicio', function (Blueprint $table) {
                $table->integer('orden')->nullable();
            });
        }
    }
};
