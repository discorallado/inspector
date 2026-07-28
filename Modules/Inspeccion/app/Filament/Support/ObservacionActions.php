<?php

namespace Modules\Inspeccion\Filament\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

/**
 * Acción de cierre de Observacion, reutilizada tanto en el recurso global
 * como en relation managers dentro de VisitaInspeccion/Tablero.
 */
class ObservacionActions
{
    public static function todas(): array
    {
        return [
            self::cerrar(),
        ];
    }

    private static function cerrar(): Action
    {
        return Action::make('cerrar')
            ->label(__('inspeccion.observacion.acciones.cerrar'))
            ->color('success')
            ->visible(fn (Observacion $record) => Gate::allows('observacion.cerrar') && ! $record->estadoObservacion->es_terminal && ! AccionesBorradoLogico::esTrashed($record))
            ->form([
                Select::make('estado_observacion_id')
                    ->label(__('inspeccion.observacion.campos.estado_observacion'))
                    ->options(fn (Observacion $record) => self::opcionesDestino($record))
                    ->required(),
                DatePicker::make('fecha_cierre')
                    ->label(__('inspeccion.observacion.campos.fecha_cierre'))
                    ->default(now())
                    ->required(),
                Textarea::make('observacion_cierre')
                    ->label(__('inspeccion.observacion.campos.observacion_cierre')),
            ])
            ->action(fn (Observacion $record, array $data) => $record->update($data));
    }

    private static function opcionesDestino(Observacion $record): array
    {
        $ids = app(TransicionEstadoGuard::class)
            ->transicionesValidasDesde(TransicionEstadoPermitida::TIPO_ESTADO_OBSERVACION, $record->estado_observacion_id);

        return EstadoObservacion::query()->whereIn('id', $ids)->pluck('nombre', 'id')->all();
    }
}
