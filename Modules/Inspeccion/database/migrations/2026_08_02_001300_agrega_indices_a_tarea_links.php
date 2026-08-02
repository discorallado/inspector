<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * /revisor sobre PR8: tarea_links.source_id/target_id se consultan en
 * cada carga del Gantt (getGanttData(): whereIn sobre ambas columnas) y
 * en cada borrado de link, sin índice — full scan a medida que crezca la
 * tabla. Migración nueva en vez de tocar
 * 2026_08_01_090300_create_tarea_links_table.php (ya corrida).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarea_links', function (Blueprint $table) {
            $table->index('source_id');
            $table->index('target_id');
        });
    }

    public function down(): void
    {
        Schema::table('tarea_links', function (Blueprint $table) {
            $table->dropIndex(['source_id']);
            $table->dropIndex(['target_id']);
        });
    }
};
