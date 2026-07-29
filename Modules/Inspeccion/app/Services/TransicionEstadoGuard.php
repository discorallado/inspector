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
    /**
     * Cache por request: transicionesValidasDesde() se llama una vez por
     * fila en los SelectColumn de ObservacionsTable/ControlCambiosTable, y
     * el par (tipo_catalogo, origen_id) se repite entre filas con el mismo
     * estado. Vive solo durante el request actual — cada request HTTP es
     * un proceso PHP nuevo (sin Octane), así que no hay riesgo de servir
     * datos viejos entre requests ni de pisar la fila que se acaba de
     * actualizar (esa fila cae en otra key de cache al cambiar su origen).
     *
     * @var array<string, Collection<int, int>>
     */
    private static array $cacheTransicionesValidas = [];

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
        $key = $tipoCatalogo.':'.($origenId ?? 'null');

        self::$cacheTransicionesValidas[$key] ??= TransicionEstadoPermitida::query()
            ->where('tipo_catalogo', $tipoCatalogo)
            ->where('estado_origen_id', $origenId)
            ->pluck('estado_destino_id');

        // Clona antes de devolver: los callers hacen ->push() sobre el
        // resultado (para agregar el estado actual del registro a las
        // opciones), y Collection::push() muta el objeto en el lugar. Sin
        // el clone, ese push() corrompería la entrada de la cache
        // compartida entre requests-fila del mismo render de tabla.
        return clone self::$cacheTransicionesValidas[$key];
    }
}
