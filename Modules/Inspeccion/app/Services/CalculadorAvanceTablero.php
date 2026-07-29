<?php

namespace Modules\Inspeccion\Services;

use Illuminate\Support\Collection;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\Tarea;

/**
 * ADR 0009 §2.2 / ADR 0012: pondera sobre Tarea.peso (vía Actividad) en
 * vez de TableroHito.peso — misma fórmula, TableroHito queda deprecado
 * (referencia histórica de solo lectura hasta el cleanup de PR9).
 */
class CalculadorAvanceTablero
{
    public function calcular(Tablero $tablero): ?float
    {
        return self::calcularSobreColeccion($tablero->tareas()->get());
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
     * con excluye_calculo = true (ej. TableroHito 'na' migrado) o sin peso
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
}
