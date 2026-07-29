<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo de /revisor sobre PR4 (ADR 0010): actividades/tareas quedaron
 * con cascadeOnDelete() sin SoftDeletes, igual que tablero_hitos (la
 * entidad que reemplazan) — pero a diferencia de tablero_hitos, van a
 * acumular trabajo real del usuario (subtareas, horas, fechas reales,
 * Kanban/Gantt en PR7/PR8). Borrar un Tablero hoy ya es posible desde
 * TableroResource sin protección — mismo patrón que ya se corrigió para
 * el historial de calidad (ver 2026_07_27_090000/090100): SoftDeletes +
 * restrictOnDelete en vez de cascadeOnDelete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('tareas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('actividades', function (Blueprint $table) {
            $table->dropForeign(['tablero_id']);
            $table->foreign('tablero_id')->references('id')->on('tableros')->restrictOnDelete();
        });

        Schema::table('tareas', function (Blueprint $table) {
            $table->dropForeign(['actividad_id']);
            $table->foreign('actividad_id')->references('id')->on('actividades')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->dropForeign(['actividad_id']);
            $table->foreign('actividad_id')->references('id')->on('actividades')->cascadeOnDelete();
        });

        Schema::table('actividades', function (Blueprint $table) {
            $table->dropForeign(['tablero_id']);
            $table->foreign('tablero_id')->references('id')->on('tableros')->cascadeOnDelete();
        });

        Schema::table('tareas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('actividades', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
