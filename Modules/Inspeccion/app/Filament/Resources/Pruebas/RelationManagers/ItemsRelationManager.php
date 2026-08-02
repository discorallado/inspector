<?php

namespace Modules\Inspeccion\Filament\Resources\Pruebas\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

/**
 * Los ítems son el snapshot tomado al crear la prueba (ver
 * Prueba::crearDesdeTemplate): no se crean ni eliminan acá, solo se
 * completa su resultado y observación.
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('categoria')
                ->label(__('inspeccion.prueba.campos.categoria'))
                ->content(fn ($record) => $record?->categoria),
            Placeholder::make('item')
                ->label(__('inspeccion.prueba.campos.item'))
                ->content(fn ($record) => $record?->item)
                ->columnSpanFull(),
            Select::make('resultado_checklist_id')
                ->label(__('inspeccion.prueba.campos.resultado'))
                ->relationship('resultadoChecklist', 'nombre')
                ->required(),
            Textarea::make('observacion')
                ->label(__('inspeccion.prueba.campos.observacion'))
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item')
            ->defaultSort('orden')
            // Agrupado por categoría (Sección de la doc de Schemas de
            // Filament aplicada acá vía groups(), el mecanismo idiomático
            // de Table para esto): calca el orden de la norma en vez de
            // una lista plana de 8+ ítems.
            ->groups([
                Group::make('categoria')
                    ->label(__('inspeccion.prueba.campos.categoria')),
            ])
            ->defaultGroup('categoria')
            ->columns([
                TextColumn::make('item')
                    ->label(__('inspeccion.prueba.campos.item'))
                    ->wrap(),
                TextColumn::make('referencia_normativa')
                    ->label(__('inspeccion.prueba.campos.referencia_normativa')),
                TextColumn::make('resultadoChecklist.nombre')
                    ->label(__('inspeccion.prueba.campos.resultado'))
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
