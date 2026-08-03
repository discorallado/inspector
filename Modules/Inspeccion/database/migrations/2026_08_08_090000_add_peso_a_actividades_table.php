<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR de peso ponderado por Actividad: CalculadorAvanceTablero pasa a
 * ponderar el avance_global entre Actividades (antes ponderaba directo
 * sobre todas las Tareas del Tablero, ignorando a qué Actividad
 * pertenecían).
 *
 * Backfill: cada Actividad existente recibe como peso la SUMA del peso de
 * sus propias Tareas (no un valor parejo como 1) — es la única forma de
 * que el avance_global no salte el día del deploy. Con peso parejo, la
 * nueva fórmula (promedio simple entre Actividades) da un número distinto
 * al de la fórmula vieja (promedio ponderado directo sobre Tareas) salvo
 * que todas las Actividades tengan la misma suma de peso de Tareas —
 * matemáticamente: si actividad.peso = Σ(tarea.peso de esa actividad),
 * Σ(actividad.peso × actividad.avance()) / Σ(actividad.peso) se reduce
 * exactamente a Σ(tarea.peso × tarea.valor) / Σ(tarea.peso), la fórmula
 * vieja. Actividades sin Tareas con peso computable quedan en 1 (no
 * afecta el cálculo: sin avance() propio, CalculadorAvanceTablero las
 * excluye igual que a una Tarea sin peso).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->decimal('peso', 5, 2)->nullable()->after('orden');
        });

        $pesosPorActividad = DB::table('tareas')
            ->select('actividad_id', DB::raw('SUM(peso) as peso_total'))
            ->where('excluye_calculo', false)
            ->whereNotNull('peso')
            ->whereNull('deleted_at')
            ->groupBy('actividad_id')
            ->pluck('peso_total', 'actividad_id');

        DB::table('actividades')->whereNull('deleted_at')->orderBy('id')->chunkById(200, function ($actividades) use ($pesosPorActividad) {
            foreach ($actividades as $actividad) {
                DB::table('actividades')
                    ->where('id', $actividad->id)
                    ->update(['peso' => $pesosPorActividad[$actividad->id] ?? 1]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropColumn('peso');
        });
    }
};
