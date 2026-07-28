<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablero y Proyecto no tienen SoftDeletes (siguen siendo borrado físico),
 * pero cascadeOnDelete() es un ON DELETE de base de datos: no distingue si
 * el hijo tiene SoftDeletes, lo borra físicamente igual. Sin este cambio,
 * borrar un Proyecto/Tablero seguiría destruyendo para siempre las Visitas,
 * Controles de Cambio y Ejecuciones de Checklist que la migración anterior
 * recién protegió con borrado lógico.
 *
 * Con restrictOnDelete(), borrar un Proyecto/Tablero que todavía tiene
 * historial (aunque esté soft-deleted) falla con una violación de FK — hay
 * que vaciarlo explícitamente primero. TableroHito no cambia: no es
 * historial de calidad, es el cronograma vigente, tiene sentido que
 * desaparezca con su Tablero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitas_inspeccion', function (Blueprint $table) {
            $table->dropForeign(['proyecto_id']);
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->restrictOnDelete();
        });

        Schema::table('control_cambios', function (Blueprint $table) {
            $table->dropForeign(['tablero_id']);
            $table->foreign('tablero_id')->references('id')->on('tableros')->restrictOnDelete();
        });

        Schema::table('checklist_ejecuciones', function (Blueprint $table) {
            $table->dropForeign(['tablero_id']);
            $table->foreign('tablero_id')->references('id')->on('tableros')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visitas_inspeccion', function (Blueprint $table) {
            $table->dropForeign(['proyecto_id']);
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->cascadeOnDelete();
        });

        Schema::table('control_cambios', function (Blueprint $table) {
            $table->dropForeign(['tablero_id']);
            $table->foreign('tablero_id')->references('id')->on('tableros')->cascadeOnDelete();
        });

        Schema::table('checklist_ejecuciones', function (Blueprint $table) {
            $table->dropForeign(['tablero_id']);
            $table->foreign('tablero_id')->references('id')->on('tableros')->cascadeOnDelete();
        });
    }
};
