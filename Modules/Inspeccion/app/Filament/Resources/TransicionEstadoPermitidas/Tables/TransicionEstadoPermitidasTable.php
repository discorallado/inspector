<?php

namespace Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransicionEstadoPermitidasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo_catalogo')
                    ->label('Catálogo')
                    ->searchable(),
                TextColumn::make('estado_origen_id')
                    ->label('ID estado origen')
                    ->placeholder('— (inicial)')
                    ->numeric(),
                TextColumn::make('estado_destino_id')
                    ->label('ID estado destino')
                    ->numeric(),
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
