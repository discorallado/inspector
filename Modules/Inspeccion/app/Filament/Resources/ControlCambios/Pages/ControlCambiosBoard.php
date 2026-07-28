<?php

namespace Modules\Inspeccion\Filament\Resources\ControlCambios\Pages;

use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Filament\Resources\ControlCambios\ControlCambioResource;
use Modules\Inspeccion\Filament\Support\ControlCambioActions;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoCambio;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardResourcePage;
use Relaticle\Flowforge\Column;

/**
 * Tablero kanban de Control de Cambios, agrupado por EstadoCambio.
 *
 * Mismo patrón que ObservacionesBoard: el movimiento de una card delega en
 * Eloquent update(), que ya dispara ControlCambioObserver::saving() (valida
 * contra TransicionEstadoGuard) — no se duplica esa lógica acá. Lo único
 * que este page agrega es la autorización, porque Flowforge no gatea
 * moveCard() por su cuenta.
 */
class ControlCambiosBoard extends BoardResourcePage
{
    protected static string $resource = ControlCambioResource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    public function getTitle(): string
    {
        return __('inspeccion.control_cambio.kanban_titulo');
    }

    public function board(Board $board): Board
    {
        return $board
            ->query(
                ControlCambio::query()->with(['tablero', 'estadoCambio'])
            )
            ->recordTitleAttribute('descripcion')
            ->columnIdentifier('estado_cambio_id')
            ->positionIdentifier('posicion')
            ->columns(
                EstadoCambio::query()
                    ->orderBy('orden')
                    ->get()
                    ->map(fn (EstadoCambio $estado) => Column::make((string) $estado->id)->label($estado->nombre))
                    ->all()
            )
            ->cardSchema(fn (Schema $schema) => $schema->components([
                TextEntry::make('tablero.tag')
                    ->label(__('inspeccion.control_cambio.campos.tablero'))
                    ->placeholder('—'),
                TextEntry::make('responsable')
                    ->label(__('inspeccion.control_cambio.campos.responsable'))
                    ->placeholder('—'),
                TextEntry::make('fecha')
                    ->label(__('inspeccion.control_cambio.campos.fecha'))
                    ->date(),
            ]))
            ->recordActions(ControlCambioActions::todas());
    }

    /**
     * Misma autorización que ya exige ControlCambioPolicy::update() para
     * cualquier cambio sobre un registro existente.
     */
    public function moveCard(
        string $cardId,
        string $targetColumnId,
        ?string $afterCardId = null,
        ?string $beforeCardId = null,
    ): void {
        if (! Gate::any(['control_cambio.proponer', 'control_cambio.decidir', 'control_cambio.implementar'])) {
            throw new AuthorizationException;
        }

        parent::moveCard($cardId, $targetColumnId, $afterCardId, $beforeCardId);
    }
}
