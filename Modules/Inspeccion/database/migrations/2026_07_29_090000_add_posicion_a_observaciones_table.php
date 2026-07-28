<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observaciones', function (Blueprint $table) {
            $table->flowforgePositionColumn('posicion');
            $table->unique(['estado_observacion_id', 'posicion'], 'observaciones_estado_posicion_unique');
        });
    }

    public function down(): void
    {
        Schema::table('observaciones', function (Blueprint $table) {
            $table->dropUnique('observaciones_estado_posicion_unique');
            $table->dropColumn('posicion');
        });
    }
};
