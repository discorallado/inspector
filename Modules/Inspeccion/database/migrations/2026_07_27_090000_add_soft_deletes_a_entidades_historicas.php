<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Borrado lógico para las entidades que son el historial de calidad del
 * módulo: perder físicamente una Visita, Observación, Control de Cambio o
 * Ejecución de Checklist es justo lo que este módulo existe para evitar
 * (ver docs/adr — hallazgo de revisión sobre cascada de borrado).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['visitas_inspeccion', 'observaciones', 'control_cambios', 'checklist_ejecuciones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (['visitas_inspeccion', 'observaciones', 'control_cambios', 'checklist_ejecuciones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
