<?php

namespace Modules\Inspeccion\Filament\Resources\ControlCambios\Tables;

use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;
use Modules\Inspeccion\Filament\Support\ControlCambioActions;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

class ControlCambiosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('tablero.tag')
                    ->label(__('inspeccion.control_cambio.campos.tablero')),
                TextColumn::make('descripcion')
                    ->label(__('inspeccion.control_cambio.campos.descripcion'))
                    ->limit(60)
                    ->searchable(),
                SelectColumn::make('estado_cambio_id')
                    ->label(__('inspeccion.control_cambio.campos.estado_cambio'))
                    ->options(fn (ControlCambio $record) => self::opcionesEstadoDestino($record))
                    ->disabled(fn (ControlCambio $record) => ! Gate::any([
                        'control_cambio.proponer',
                        'control_cambio.decidir',
                        'control_cambio.implementar',
                    ]) || AccionesBorradoLogico::esTrashed($record)),
                TextColumn::make('responsable')
                    ->label(__('inspeccion.control_cambio.campos.responsable')),
                TextColumn::make('fecha')
                    ->label(__('inspeccion.control_cambio.campos.fecha'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('estado_cambio_id')
                    ->label(__('inspeccion.control_cambio.campos.estado_cambio'))
                    ->options(EstadoCambio::query()->pluck('nombre', 'id')),
                ...AccionesBorradoLogico::filtros(),
            ])
            ->recordActions([
                ActionGroup::make(ControlCambioActions::todas()),
                AccionesBorradoLogico::editar(),
                ...AccionesBorradoLogico::registro(),
            ])
            ->toolbarActions([
                AccionesBorradoLogico::acciones(),
            ]);
    }

    /**
     * Ofrece los estados alcanzables desde el actual según
     * transiciones_estado_permitidas, filtrados ADEMÁS por la ability que
     * exige esa transición puntual (origen -> destino), no solo el
     * destino — igual que distingue ControlCambioActions entre sus
     * acciones. Filtrar solo por destino deja un hueco: "-> aprobado"
     * requiere `decidir` cuando viene de "propuesto", pero requiere
     * `implementar` cuando viene de "implementado" (transición
     * "desimplementar"); un supervisor con `decidir` (sin `implementar`)
     * podía revertir un cambio Implementado -> Aprobado por el <select>
     * aunque el botón "Desimplementar" se lo niegue correctamente.
     * El estado actual del registro siempre se incluye, sin filtrar por
     * ability, para que el <select> tenga una opción que coincida con el
     * valor seleccionado.
     *
     * @return array<int, string>
     */
    private static function opcionesEstadoDestino(ControlCambio $record): array
    {
        $origenCodigo = $record->estadoCambio->codigo;

        $idsAlcanzables = app(TransicionEstadoGuard::class)
            ->transicionesValidasDesde(TransicionEstadoPermitida::TIPO_ESTADO_CAMBIO, $record->estado_cambio_id);

        $idsAutorizados = EstadoCambio::query()
            ->whereIn('id', $idsAlcanzables)
            ->get()
            ->filter(fn (EstadoCambio $estado) => Gate::allows(self::abilityRequerida($origenCodigo, $estado->codigo)))
            ->pluck('id')
            ->push($record->estado_cambio_id)
            ->unique();

        return EstadoCambio::query()->whereIn('id', $idsAutorizados)->orderBy('orden')->pluck('nombre', 'id')->all();
    }

    /**
     * Misma matriz de abilities que ControlCambioActions: aprobar/rechazar
     * (desde propuesto o aprobado) exigen `decidir`; implementar (desde
     * aprobado) y desimplementar (desde implementado) exigen `implementar`.
     */
    private static function abilityRequerida(string $origenCodigo, string $destinoCodigo): string
    {
        return match (true) {
            $origenCodigo === 'aprobado' && $destinoCodigo === 'implementado' => 'control_cambio.implementar',
            $origenCodigo === 'implementado' && $destinoCodigo === 'aprobado' => 'control_cambio.implementar',
            default => 'control_cambio.decidir',
        };
    }
}
