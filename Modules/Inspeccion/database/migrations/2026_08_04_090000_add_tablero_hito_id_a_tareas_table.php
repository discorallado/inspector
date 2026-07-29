<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo de /revisor (checkpoint pre-PR6): MigrarHitosATareasCommand
 * matcheaba por `actividad_id`+`code`, y `code` se deriva de
 * `TableroHito.item` (un TextInput libre). Si `item` cambiaba entre dos
 * corridas del comando, la Tarea existente dejaba de matchear y el
 * comando creaba una huérfana en vez de actualizarla. `tablero_hito_id`
 * es una clave de matcheo estable e independiente del contenido editable
 * de `TableroHito` — solo existe para este puente temporal con el
 * sistema viejo, se va con el cleanup de PR9.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->foreignId('tablero_hito_id')->nullable()->after('parent_tarea_id')
                ->constrained('tablero_hitos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tablero_hito_id');
        });
    }
};
