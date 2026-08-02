<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Los ítems son el snapshot tomado al crear la ejecución (ver
 * ChecklistEjecucion::crearDesdeTemplate): no se crean ni eliminan acá,
 * solo se completa su resultado y observación durante la visita.
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('categoria')
                ->label(__('inspeccion.checklist.campos.categoria'))
                ->content(fn ($record) => $record?->categoria),
            Placeholder::make('item')
                ->label(__('inspeccion.checklist.campos.item'))
                ->content(fn ($record) => $record?->item)
                ->columnSpanFull(),
            Select::make('resultado_checklist_id')
                ->label(__('inspeccion.checklist.campos.resultado'))
                ->relationship('resultadoChecklist', 'nombre')
                ->required(),
            Textarea::make('observacion')
                ->label(__('inspeccion.checklist.campos.observacion'))
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item')
            ->defaultSort('orden')
            ->columns([
                TextColumn::make('categoria')
                    ->label(__('inspeccion.checklist.campos.categoria')),
                TextColumn::make('item')
                    ->label(__('inspeccion.checklist.campos.item'))
                    ->wrap(),
                TextColumn::make('referencia_normativa')
                    ->label(__('inspeccion.checklist.campos.referencia_normativa')),
                TextColumn::make('resultadoChecklist.nombre')
                    ->label(__('inspeccion.checklist.campos.resultado'))
                    ->badge()
                    ->placeholder('—'),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public function canCreate(): bool
    {
        return false;
    }
}
