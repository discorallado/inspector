<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Rollup de solo lectura: las Observaciones se crean/editan desde el
 * contexto de la VisitaInspeccion, no desde el Tablero.
 */
class ObservacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'observaciones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->columns([
                TextColumn::make('visitaInspeccion.fecha')
                    ->label(__('inspeccion.visita_inspeccion.campos.fecha'))
                    ->date(),
                TextColumn::make('tipoObservacion.nombre')
                    ->label(__('inspeccion.observacion.campos.tipo_observacion'))
                    ->badge(),
                TextColumn::make('severidad.nombre')
                    ->label(__('inspeccion.observacion.campos.severidad'))
                    ->placeholder('—'),
                TextColumn::make('estadoObservacion.nombre')
                    ->label(__('inspeccion.observacion.campos.estado_observacion'))
                    ->badge(),
                TextColumn::make('fecha_compromiso')
                    ->label(__('inspeccion.observacion.campos.fecha_compromiso'))
                    ->date()
                    ->placeholder('—'),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
