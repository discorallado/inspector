<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChecklistEjecucionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visitaInspeccion.fecha')
                    ->label(__('inspeccion.visita_inspeccion.campos.fecha'))
                    ->date(),
                TextColumn::make('tablero.tag')
                    ->label(__('inspeccion.observacion.campos.tablero')),
                TextColumn::make('checklistTemplate.nombre')
                    ->label(__('inspeccion.checklist.template.singular')),
                TextColumn::make('items_count')
                    ->label(__('inspeccion.checklist.campos.item'))
                    ->counts('items'),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
