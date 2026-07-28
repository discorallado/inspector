<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('control_cambios', function (Blueprint $table) {
            $table->flowforgePositionColumn('posicion');
            $table->unique(['estado_cambio_id', 'posicion'], 'control_cambios_estado_posicion_unique');
        });
    }

    public function down(): void
    {
        Schema::table('control_cambios', function (Blueprint $table) {
            $table->dropUnique('control_cambios_estado_posicion_unique');
            $table->dropColumn('posicion');
        });
    }
};
