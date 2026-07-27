<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreignId('visita_inspeccion_id')->constrained('visitas_inspeccion')->cascadeOnDelete();
            $table->foreignId('tablero_id')->nullable()->constrained('tableros')->nullOnDelete();
            $table->foreignId('tablero_hito_id')->nullable()->constrained('tablero_hitos')->nullOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades');
            $table->foreignId('tipo_observacion_id')->constrained('tipos_observacion');
            $table->foreignId('severidad_id')->nullable()->constrained('severidades');
            $table->text('descripcion');
            $table->string('responsable')->nullable();
            $table->date('fecha_compromiso')->nullable();
            $table->foreignId('estado_observacion_id')->constrained('estados_observacion');
            $table->date('fecha_cierre')->nullable();
            $table->text('observacion_cierre')->nullable();
            $table->timestamps();

            $table->index(['tablero_id', 'estado_observacion_id']);
            $table->index(['fecha_compromiso', 'estado_observacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones');
    }
};
