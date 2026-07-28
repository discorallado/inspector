<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revierte 2026_07_29_090000 y 2026_07_30_090000: el kanban de
 * Observaciones/Control de Cambios se reemplazó por una tabla con
 * columna de estado editable inline (ver ADR 0008) — la columna
 * `posicion` que requería Flowforge para el drag-and-drop ya no la usa
 * nada. No se editan las migraciones originales (ya corridas), se agrega
 * una nueva que deshace el cambio.
 *
 * `estado_observacion_id`/`estado_cambio_id` son FK, y el único índice
 * que las tenía como columna izquierda era justamente el unique
 * (estado_*, posicion) que se está borrando — MariaDB no deja dropear un
 * índice del que depende una foreign key (error 1553). Hay que crear un
 * índice simple de reemplazo antes de dropear el compuesto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observaciones', function (Blueprint $table) {
            $table->index('estado_observacion_id', 'observaciones_estado_observacion_id_idx');
            $table->dropUnique('observaciones_estado_posicion_unique');
            $table->dropColumn('posicion');
        });

        Schema::table('control_cambios', function (Blueprint $table) {
            $table->index('estado_cambio_id', 'control_cambios_estado_cambio_id_idx');
            $table->dropUnique('control_cambios_estado_posicion_unique');
            $table->dropColumn('posicion');
        });
    }

    public function down(): void
    {
        Schema::table('observaciones', function (Blueprint $table) {
            $table->flowforgePositionColumn('posicion');
            $table->unique(['estado_observacion_id', 'posicion'], 'observaciones_estado_posicion_unique');
            $table->dropIndex('observaciones_estado_observacion_id_idx');
        });

        Schema::table('control_cambios', function (Blueprint $table) {
            $table->flowforgePositionColumn('posicion');
            $table->unique(['estado_cambio_id', 'posicion'], 'control_cambios_estado_posicion_unique');
            $table->dropIndex('control_cambios_estado_cambio_id_idx');
        });
    }
};
