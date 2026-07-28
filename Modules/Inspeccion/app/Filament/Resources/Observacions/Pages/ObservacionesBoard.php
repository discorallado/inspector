<?php

namespace Modules\Inspeccion\Filament\Resources\Observacions\Pages;

use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Filament\Resources\Observacions\ObservacionResource;
use Modules\Inspeccion\Filament\Support\ObservacionActions;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\Observacion;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardResourcePage;
use Relaticle\Flowforge\Column;

/**
 * Tablero kanban de Observaciones, agrupado por EstadoObservacion.
 *
 * El movimiento de una card (moveCard) delega en Eloquent update(), que ya
 * dispara ObservacionObserver::saving() — ahí vive la validación real contra
 * TransicionEstadoGuard (no se duplica acá). Lo único que este page agrega
 * es la autorización: Flowforge no gatea moveCard() por su cuenta.
 */
class ObservacionesBoard extends BoardResourcePage
{
    protected static string $resource = ObservacionResource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    public function getTitle(): string
    {
        return __('inspeccion.observacion.kanban_titulo');
    }

    public function board(Board $board): Board
    {
        return $board
            ->query(
                Observacion::query()->with(['tablero', 'tipoObservacion', 'severidad', 'estadoObservacion'])
            )
            ->recordTitleAttribute('descripcion')
            ->columnIdentifier('estado_observacion_id')
            ->positionIdentifier('posicion')
            ->columns(
                EstadoObservacion::query()
                    ->orderBy('orden')
                    ->get()
                    ->map(fn (EstadoObservacion $estado) => Column::make((string) $estado->id)->label($estado->nombre))
                    ->all()
            )
            ->cardSchema(fn (Schema $schema) => $schema->components([
                TextEntry::make('tablero.tag')
                    ->label(__('inspeccion.observacion.campos.tablero'))
                    ->placeholder('—'),
                TextEntry::make('tipoObservacion.nombre')
                    ->label(__('inspeccion.observacion.campos.tipo_observacion'))
                    ->badge(),
                TextEntry::make('severidad.nombre')
                    ->label(__('inspeccion.observacion.campos.severidad'))
                    ->badge()
                    ->placeholder('—'),
                TextEntry::make('fecha_compromiso')
                    ->label(__('inspeccion.observacion.campos.fecha_compromiso'))
                    ->date()
                    ->placeholder('—')
                    ->color(fn (Observacion $record) => $record->estaVencida() ? 'danger' : null),
            ]))
            ->recordActions(ObservacionActions::todas());
    }

    /**
     * Flowforge no gatea moveCard() por su cuenta: cualquiera que vea el
     * board podría arrastrar una card. Se exige la misma ability que ya
     * gobierna cerrar/cambiar de estado una Observacion.
     */
    public function moveCard(
        string $cardId,
        string $targetColumnId,
        ?string $afterCardId = null,
        ?string $beforeCardId = null,
    ): void {
        Gate::authorize('observacion.cerrar');

        parent::moveCard($cardId, $targetColumnId, $afterCardId, $beforeCardId);
    }
}
