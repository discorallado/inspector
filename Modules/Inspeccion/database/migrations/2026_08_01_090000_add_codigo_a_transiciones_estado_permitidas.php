<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR 0009/0010: generaliza transiciones_estado_permitidas para validar
 * también Tarea.status, que es un enum PHP (TaskStatus) y no una tabla de
 * catálogo con id — no tiene un estado_origen_id/estado_destino_id al que
 * apuntar. Se agregan columnas de código en paralelo a las de id, en vez
 * de reescribir las 3 filas de tipo_catalogo existentes (estado_avance,
 * estado_observacion, estado_cambio) a texto: son basados en catálogo real
 * y ya están probados así, cambiarlos no aporta nada y aumenta el blast
 * radius de este PR.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transiciones_estado_permitidas', function (Blueprint $table) {
            // Las filas basadas en código (tipo_catalogo = 'tarea_status')
            // dejan estado_destino_id en null; por eso deja de ser NOT NULL.
            $table->unsignedBigInteger('estado_destino_id')->nullable()->change();

            $table->string('estado_origen_codigo')->nullable()->after('estado_origen_id');
            $table->string('estado_destino_codigo')->nullable()->after('estado_destino_id');

            $table->index(['tipo_catalogo', 'estado_origen_codigo'], 'transiciones_estado_tipo_origen_codigo_idx');
        });
    }

    public function down(): void
    {
        // Sin esto, el rollback falla en cuanto exista al menos una fila
        // tipo_catalogo = 'tarea_status' sembrada (TransicionEstadoPermitidaSeeder
        // las crea con estado_destino_id NULL) — el ALTER a NOT NULL de abajo
        // las rechaza. Esas filas solo existen porque este mismo up() habilitó
        // las columnas de código; al revertir el esquema, revierte los datos
        // que dependían de él.
        DB::table('transiciones_estado_permitidas')->whereNull('estado_destino_id')->delete();

        Schema::table('transiciones_estado_permitidas', function (Blueprint $table) {
            $table->dropIndex('transiciones_estado_tipo_origen_codigo_idx');
            $table->dropColumn(['estado_origen_codigo', 'estado_destino_codigo']);
            $table->unsignedBigInteger('estado_destino_id')->nullable(false)->change();
        });
    }
};
