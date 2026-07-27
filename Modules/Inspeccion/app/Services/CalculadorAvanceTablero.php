<?php

namespace Modules\Inspeccion\Services;

use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\TableroHito;

class CalculadorAvanceTablero
{
    /**
     * avance% = Σ(peso × valor_estado) / Σ(peso) × 100, excluyendo hitos
     * cuyo estado tiene excluye_calculo = true (ej. N/A).
     */
    public function calcular(Tablero $tablero): ?float
    {
        $hitos = $tablero->tableroHitos()
            ->with('estadoAvance')
            ->get()
            ->reject(fn (TableroHito $hito) => $hito->estadoAvance->excluye_calculo);

        $pesoTotal = $hitos->sum(fn (TableroHito $hito) => (float) $hito->peso);

        if ($pesoTotal <= 0) {
            return null;
        }

        $avanceLogrado = $hitos->sum(
            fn (TableroHito $hito) => (float) $hito->peso * (float) $hito->estadoAvance->valor
        );

        return round(($avanceLogrado / $pesoTotal) * 100, 2);
    }

    public function recalcularYGuardar(Tablero $tablero): void
    {
        $tablero->forceFill([
            'avance_global' => $this->calcular($tablero),
            'avance_calculado_at' => now(),
        ])->saveQuietly();
    }
}
