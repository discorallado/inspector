<?php

namespace Modules\Inspeccion\Services;

use Illuminate\Support\Collection;
use Modules\Inspeccion\Exceptions\TransicionEstadoInvalidaException;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;

/**
 * Valida saltos de estado contra la tabla transiciones_estado_permitidas,
 * en vez de codificar la máquina de estados en clases PHP (ver ADR).
 */
class TransicionEstadoGuard
{
    public function puedeTransicionar(string $tipoCatalogo, ?int $origenId, int $destinoId): bool
    {
        if ($origenId === $destinoId) {
            return true;
        }

        return TransicionEstadoPermitida::query()
            ->where('tipo_catalogo', $tipoCatalogo)
            ->where('estado_origen_id', $origenId)
            ->where('estado_destino_id', $destinoId)
            ->exists();
    }

    public function validar(string $tipoCatalogo, ?int $origenId, int $destinoId): void
    {
        if (! $this->puedeTransicionar($tipoCatalogo, $origenId, $destinoId)) {
            throw new TransicionEstadoInvalidaException($tipoCatalogo, $origenId, $destinoId);
        }
    }

    /**
     * @return Collection<int, int> IDs de estado_destino_id alcanzables desde $origenId.
     */
    public function transicionesValidasDesde(string $tipoCatalogo, ?int $origenId): Collection
    {
        return TransicionEstadoPermitida::query()
            ->where('tipo_catalogo', $tipoCatalogo)
            ->where('estado_origen_id', $origenId)
            ->pluck('estado_destino_id');
    }
}
