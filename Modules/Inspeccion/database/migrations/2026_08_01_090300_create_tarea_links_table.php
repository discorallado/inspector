<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR 0009 §2.2: espejo de TaskLink de axon (dependencias del Gantt,
 * PR8). `type`: 0=FS 1=SS 2=FF 3=SF (mismos códigos que axon). Sin FK
 * declarada hacia tareas en source_id/target_id porque un link puede
 * apuntar a una Actividad (Gantt de axon permite dependencias entre
 * fases, no solo tareas) — mismo diseño no-normalizado que axon.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarea_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('target_id');
            $table->unsignedTinyInteger('type')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarea_links');
    }
};
