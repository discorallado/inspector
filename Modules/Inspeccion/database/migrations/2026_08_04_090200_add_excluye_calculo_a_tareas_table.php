<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR 0012 (parte 2, decidido con el usuario antes de PR6): al migrar,
 * EstadoAvance.codigo='na' se mapeó a TaskStatus::Bloqueada (no hay valor
 * "N/A" en el enum) — pero Bloqueada también es el estado normal de una
 * tarea real trabada, que SÍ debe contar en el avance (como 0%, no
 * excluida). Esta columna desacopla "excluido del cálculo" de status,
 * para no perder ninguna de las dos semánticas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->boolean('excluye_calculo')->default(false)->after('peso');
        });
    }

    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->dropColumn('excluye_calculo');
        });
    }
};
