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
     * Antecedente para el futuro (evaluado en /revisor, se decidió no
     * resolver ahora): esta cache no tiene invalidación. Es segura en
     * requests HTTP y en tests (RefreshDatabase resetea los IDs solos
     * entre casos), pero un proceso de vida larga que llame al guard
     * muchas veces mientras `transiciones_estado_permitidas` cambia a
     * mitad de camino (p. ej. un comando Artisan de migración de datos,
     * como el que planea PR5 del ADR 0009) podría ver resultados viejos.
     * Si eso llega a pasar, la solución es limpiar `$cacheTransicionesValidas`
     * al terminar ese comando, no sacar la cache.
     *
     * @var array<string, Collection<int, int>>
     */
    private static array $cacheTransicionesValidas = [];

    /**
     * Mismo propósito que $cacheTransicionesValidas, para las variantes
     * *PorCodigo (ADR 0009: Tarea.status es un enum, no una tabla de
     * catálogo con id).
     *
     * @var array<string, Collection<int, string>>
     */
    private static array $cacheTransicionesValidasPorCodigo = [];

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

    /**
     * Variante de puedeTransicionar() para catálogos basados en código
     * string en vez de id de catálogo (ADR 0009: Tarea.status). Métodos
     * separados en vez de ensanchar puedeTransicionar()/validar() a tipos
     * unión (int|string): el tipo de $destinoId no alcanza para elegir
     * columna cuando $origenId es null (creación), y sniffear el tipo acá
     * sería frágil — un id numérico pasado por error como string caería
     * en la rama equivocada en silencio.
     */
    public function puedeTransicionarPorCodigo(string $tipoCatalogo, ?string $origenCodigo, string $destinoCodigo): bool
    {
        if ($origenCodigo === $destinoCodigo) {
            return true;
        }

        return TransicionEstadoPermitida::query()
            ->where('tipo_catalogo', $tipoCatalogo)
            ->where('estado_origen_codigo', $origenCodigo)
            ->where('estado_destino_codigo', $destinoCodigo)
            ->exists();
    }

    public function validarPorCodigo(string $tipoCatalogo, ?string $origenCodigo, string $destinoCodigo): void
    {
        if (! $this->puedeTransicionarPorCodigo($tipoCatalogo, $origenCodigo, $destinoCodigo)) {
            throw new TransicionEstadoInvalidaException($tipoCatalogo, $origenCodigo, $destinoCodigo);
        }
    }

    /**
     * @return Collection<int, string> códigos de estado_destino_codigo alcanzables desde $origenCodigo.
     */
    public function transicionesValidasDesdePorCodigo(string $tipoCatalogo, ?string $origenCodigo): Collection
    {
        $key = $tipoCatalogo.':'.($origenCodigo ?? 'null');

        self::$cacheTransicionesValidasPorCodigo[$key] ??= TransicionEstadoPermitida::query()
            ->where('tipo_catalogo', $tipoCatalogo)
            ->where('estado_origen_codigo', $origenCodigo)
            ->pluck('estado_destino_codigo');

        return clone self::$cacheTransicionesValidasPorCodigo[$key];
    }
}
