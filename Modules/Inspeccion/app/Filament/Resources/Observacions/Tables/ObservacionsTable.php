<?php

namespace Modules\Inspeccion\Filament\Resources\Observacions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inspeccion\Filament\Support\ObservacionActions;
use Modules\Inspeccion\Models\Especialidad;
use Modules\Inspeccion\Models\EstadoObservacion;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\TipoObservacion;

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
                TextColumn::make('estadoObservacion.nombre')
                    ->label(__('inspeccion.observacion.campos.estado_observacion'))
                    ->badge(),
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
            ])
            ->recordActions([
                ...ObservacionActions::todas(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
