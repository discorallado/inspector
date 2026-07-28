<?php

namespace Modules\Inspeccion\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('inspeccion.usuario.campos.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('inspeccion.usuario.campos.email'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('inspeccion.usuario.campos.role'))
                    ->formatStateUsing(fn (?string $state) => $state ? Str::headline($state) : null)
                    ->placeholder(__('inspeccion.usuario.sin_rol'))
                    ->color(fn (?string $state) => $state ? 'success' : 'danger')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('inspeccion.usuario.campos.role'))
                    ->options(collect(config('inspeccion.roles'))->mapWithKeys(
                        fn (string $rol) => [$rol => Str::headline($rol)]
                    )),
            ])
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
