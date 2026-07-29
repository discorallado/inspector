<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo de /revisor (checkpoint pre-PR6): tareas.code no tenía
 * restricción real en BD — mismo patrón que ya usa tableros con
 * unique(['proyecto_id', 'tag']). Corrido inspeccion:migrar-hitos-a-tareas
 * fresco contra MariaDB real antes de este migration: los 234 registros
 * ya migrados no chocan (tag+item ya era único en la práctica).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->unique(['actividad_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->dropUnique(['actividad_id', 'code']);
        });
    }
};
