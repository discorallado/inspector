<?php

namespace Modules\Inspeccion\Filament\Resources\Observacions\Tables;

use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Modules\Inspeccion\Filament\Support\AccionesBorradoLogico;
use Modules\Inspeccion\Filament\Support\ObservacionActions;
use Modules\Inspeccion\Models\Especialidad;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\Observacion;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\TipoObservacion;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;
use Modules\Inspeccion\Services\TransicionEstadoGuard;

class ObservacionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('tablero.tag')
                    ->label(__('inspeccion.observacion.campos.tablero'))
                    ->placeholder('—'),
                TextColumn::make('especialidad.nombre')
                    ->label(__('inspeccion.observacion.campos.especialidad')),
                TextColumn::make('tipoObservacion.nombre')
                    ->label(__('inspeccion.observacion.campos.tipo_observacion'))
                    ->badge(),
                TextColumn::make('severidad.nombre')
                    ->label(__('inspeccion.observacion.campos.severidad'))
                    ->placeholder('—'),
                TextColumn::make('descripcion')
                    ->label(__('inspeccion.observacion.campos.descripcion'))
                    ->limit(60)
                    ->searchable(),
                SelectColumn::make('estado_observacion_id')
                    ->label(__('inspeccion.observacion.campos.estado_observacion'))
                    ->options(fn (Observacion $record) => self::opcionesEstadoDestino($record))
                    ->disabled(fn (Observacion $record) => ! Gate::allows('observacion.cerrar') || AccionesBorradoLogico::esTrashed($record)),
                TextColumn::make('fecha_compromiso')
                    ->label(__('inspeccion.observacion.campos.fecha_compromiso'))
                    ->date()
                    ->placeholder('—')
                    ->color(fn ($record) => $record->estaVencida() ? 'danger' : null),
            ])
            ->filters([
                SelectFilter::make('tipo_observacion_id')
                    ->label(__('inspeccion.observacion.campos.tipo_observacion'))
                    ->options(TipoObservacion::query()->pluck('nombre', 'id')),
                SelectFilter::make('estado_observacion_id')
                    ->label(__('inspeccion.observacion.campos.estado_observacion'))
                    ->options(EstadoObservacion::query()->pluck('nombre', 'id')),
                SelectFilter::make('tablero_id')
                    ->label(__('inspeccion.observacion.campos.tablero'))
                    ->options(Tablero::query()->pluck('tag', 'id')),
                SelectFilter::make('especialidad_id')
                    ->label(__('inspeccion.observacion.campos.especialidad'))
                    ->options(Especialidad::query()->pluck('nombre', 'id')),
                Filter::make('vencidas')
                    ->label(__('inspeccion.observacion.vencida'))
                    ->query(fn (Builder $query) => $query->vencidas()),
                ...AccionesBorradoLogico::filtros(),
            ])
            ->recordActions([
                ...ObservacionActions::todas(),
                AccionesBorradoLogico::editar(),
                ...AccionesBorradoLogico::registro(),
            ])
            ->toolbarActions([
                AccionesBorradoLogico::acciones(),
            ]);
    }

    /**
     * Mismo criterio que ObservacionActions::opcionesDestino(): solo
     * ofrece como opciones los estados alcanzables desde el estado actual
     * según transiciones_estado_permitidas, más el propio estado actual
     * (si no, el <select> queda sin una opción que coincida con el
     * valor seleccionado).
     *
     * @return array<int, string>
     */
    private static function opcionesEstadoDestino(Observacion $record): array
    {
        $ids = app(TransicionEstadoGuard::class)
            ->transicionesValidasDesde(TransicionEstadoPermitida::TIPO_ESTADO_OBSERVACION, $record->estado_observacion_id)
            ->push($record->estado_observacion_id)
            ->unique();

        return EstadoObservacion::query()->whereIn('id', $ids)->orderBy('orden')->pluck('nombre', 'id')->all();
    }
}
