<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transiciones_estado_permitidas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            // Discriminador polimórfico simple: 'estado_avance' | 'estado_observacion' | 'estado_cambio'.
            // No lleva FK real porque origen/destino apuntan a tablas distintas según este valor.
            $table->string('tipo_catalogo');
            $table->unsignedBigInteger('estado_origen_id')->nullable();
            $table->unsignedBigInteger('estado_destino_id');
            $table->timestamps();

            $table->index(['tipo_catalogo', 'estado_origen_id'], 'transiciones_estado_tipo_origen_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transiciones_estado_permitidas');
    }
};
