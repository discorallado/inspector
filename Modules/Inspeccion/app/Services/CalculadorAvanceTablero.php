<?php

namespace Modules\Inspeccion\Services;

use Illuminate\Support\Collection;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\Tarea;

/**
 * ADR 0009 §2.2 / ADR 0012: pondera sobre Tarea.peso (vía Actividad) en
 * vez de HitoLegado.peso — misma fórmula, HitoLegado queda deprecado
 * (referencia histórica de solo lectura hasta el cleanup de PR9).
 *
 * ADR de peso ponderado por Actividad: el avance_global del Tablero pasa
 * a ponderar entre Actividades (Actividad.peso × Actividad.avance()) en
 * vez de ponderar directo sobre todas las Tareas de una sola pasada. Con
 * fallback a la fórmula vieja si ninguna Actividad tiene peso asignado
 * (Tableros que todavía no migraron a pesos por Actividad).
 */
class CalculadorAvanceTablero
{
    public function calcular(Tablero $tablero): ?float
    {
        $porActividad = self::calcularSobreActividades($tablero->actividades()->get());

        return $porActividad ?? self::calcularSobreColeccion($tablero->tareas()->get());
    }

    public function recalcularYGuardar(Tablero $tablero): void
    {
        $tablero->forceFill([
            'avance_global' => $this->calcular($tablero),
            'avance_calculado_at' => now(),
        ])->saveQuietly();
    }

    /**
     * avance% = Σ(peso × valor_estado) / Σ(peso) × 100, excluyendo tareas
     * con excluye_calculo = true (ej. HitoLegado 'na' migrado) o sin peso
     * asignado (peso nulo no participa del ponderado). Pública y estática
     * para reutilizarse también a nivel Actividad (columna "avance" de
     * ActividadesRelationManager), sin duplicar la fórmula.
     *
     * @param  Collection<int, Tarea>  $tareas
     */
    public static function calcularSobreColeccion(Collection $tareas): ?float
    {
        $tareas = $tareas->reject(fn (Tarea $tarea) => $tarea->excluye_calculo || $tarea->peso === null);

        $pesoTotal = $tareas->sum(fn (Tarea $tarea) => (float) $tarea->peso);

        if ($pesoTotal <= 0) {
            return null;
        }

        $avanceLogrado = $tareas->sum(
            fn (Tarea $tarea) => (float) $tarea->peso * $tarea->status->valor()
        );

        return round(($avanceLogrado / $pesoTotal) * 100, 2);
    }

    /**
     * avance_global% = Σ(actividad.peso × actividad.avance()) / Σ(actividad.peso),
     * excluyendo Actividades sin peso asignado o cuyo avance() propio es
     * null (sin Tareas con peso computable — mismo criterio de "no
     * participa" que ya aplica a Tarea, no se cuenta como 0%). null si no
     * queda ninguna Actividad computable — quien llama decide el fallback
     * (ver calcular()).
     *
     * @param  Collection<int, Actividad>  $actividades
     */
    public static function calcularSobreActividades(Collection $actividades): ?float
    {
        $actividades = $actividades
            ->reject(fn (Actividad $actividad) => $actividad->peso === null)
            ->map(fn (Actividad $actividad) => [$actividad, $actividad->avance()])
            ->filter(fn (array $par) => $par[1] !== null);

        $pesoTotal = $actividades->sum(fn (array $par) => (float) $par[0]->peso);

        if ($pesoTotal <= 0) {
            return null;
        }

        $avanceLogrado = $actividades->sum(fn (array $par) => (float) $par[0]->peso * $par[1]);

        return round($avanceLogrado / $pesoTotal, 2);
    }
}
