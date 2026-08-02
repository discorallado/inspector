<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;
use Modules\Inspeccion\Models\Tablero;

class TablerosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tag')
                    ->label(__('inspeccion.tablero.campos.tag'))
                    ->searchable(),
                TextColumn::make('proyecto.nombre')
                    ->label(__('inspeccion.tablero.campos.proyecto'))
                    ->searchable(),
                TextColumn::make('nombre')
                    ->label(__('inspeccion.tablero.campos.nombre'))
                    ->searchable(),
                TextColumn::make('fabricante')
                    ->label(__('inspeccion.tablero.campos.fabricante'))
                    ->searchable(),
                TextColumn::make('avance_global')
                    ->label(__('inspeccion.tablero.campos.avance_global'))
                    ->suffix('%')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                Action::make('kanban')
                    ->label(__('inspeccion.tarea.kanban.title'))
                    ->icon(Heroicon::OutlinedViewColumns)
                    ->url(fn (Tablero $record): string => TableroResource::getUrl('kanban', ['record' => $record])),
                Action::make('gantt')
                    ->label(__('inspeccion.tarea.gantt.title'))
                    ->icon(Heroicon::OutlinedChartBar)
                    ->url(fn (Tablero $record): string => TableroResource::getUrl('gantt', ['record' => $record])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
