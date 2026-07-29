<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo de /revisor sobre PR5 (ADR 0011): MigrarHitosATareasCommand
 * usa grupo_hitos.nombre como parte de la clave natural para no duplicar
 * Actividad al re-correr el comando — sin un índice único, dos GrupoHito
 * con el mismo nombre (posible desde el Filament de Configuración, sin
 * validación hasta este commit) se fusionarían en silencio bajo una sola
 * Actividad. El catálogo real (8 grupos) ya tiene nombres distintos, así
 * que esto corre limpio sin backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupo_hitos', function (Blueprint $table) {
            $table->unique('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('grupo_hitos', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });
    }
};
