<?php

namespace Modules\Inspeccion\Filament\Support;

use Filament\Actions\Action;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoCambio;

/**
 * Acciones de transición de ControlCambio, reutilizadas tanto en el recurso
 * global como en el relation manager dentro de Tablero.
 */
class ControlCambioActions
{
    public static function todas(): array
    {
        return [
            self::aprobar(),
            self::rechazar(),
            self::implementar(),
            self::desimplementar(),
        ];
    }

    private static function aprobar(): Action
    {
        return Action::make('aprobar')
            ->label(__('inspeccion.control_cambio.acciones.aprobar'))
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (ControlCambio $record) => Gate::allows('control_cambio.decidir') && $record->estadoCambio->codigo === 'propuesto' && ! AccionesBorradoLogico::esTrashed($record))
            ->action(fn (ControlCambio $record) => $record->update([
                'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'aprobado')->value('id'),
            ]));
    }

    private static function rechazar(): Action
    {
        return Action::make('rechazar')
            ->label(__('inspeccion.control_cambio.acciones.rechazar'))
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (ControlCambio $record) => Gate::allows('control_cambio.decidir') && in_array($record->estadoCambio->codigo, ['propuesto', 'aprobado'], true) && ! AccionesBorradoLogico::esTrashed($record))
            ->action(fn (ControlCambio $record) => $record->update([
                'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'rechazado')->value('id'),
            ]));
    }

    private static function implementar(): Action
    {
        return Action::make('implementar')
            ->label(__('inspeccion.control_cambio.acciones.implementar'))
            ->color('primary')
            ->requiresConfirmation()
            ->visible(fn (ControlCambio $record) => Gate::allows('control_cambio.implementar') && $record->estadoCambio->codigo === 'aprobado' && ! AccionesBorradoLogico::esTrashed($record))
            ->action(fn (ControlCambio $record) => $record->update([
                'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'implementado')->value('id'),
            ]));
    }

    /**
     * Revierte un cambio ya marcado como implementado, por si se marcó por
     * error o hay que reabrirlo. Misma ability que implementar(): quien
     * puede marcar la implementación puede deshacerla.
     */
    private static function desimplementar(): Action
    {
        return Action::make('desimplementar')
            ->label(__('inspeccion.control_cambio.acciones.desimplementar'))
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (ControlCambio $record) => Gate::allows('control_cambio.implementar') && $record->estadoCambio->codigo === 'implementado' && ! AccionesBorradoLogico::esTrashed($record))
            ->action(fn (ControlCambio $record) => $record->update([
                'estado_cambio_id' => EstadoCambio::query()->where('codigo', 'aprobado')->value('id'),
            ]));
    }
}
