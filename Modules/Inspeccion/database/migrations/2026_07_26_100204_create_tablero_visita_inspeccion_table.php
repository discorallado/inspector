<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tablero_visita_inspeccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tablero_id')->constrained('tableros')->cascadeOnDelete();
            $table->foreignId('visita_inspeccion_id')->constrained('visitas_inspeccion')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tablero_id', 'visita_inspeccion_id'], 'tablero_visita_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tablero_visita_inspeccion');
    }
};
