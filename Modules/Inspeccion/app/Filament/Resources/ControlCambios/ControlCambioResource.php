<?php

namespace Modules\Inspeccion\Filament\Resources\ControlCambios;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Resources\ControlCambios\Pages\ControlCambiosBoard;
use Modules\Inspeccion\Filament\Resources\ControlCambios\Pages\CreateControlCambio;
use Modules\Inspeccion\Filament\Resources\ControlCambios\Pages\EditControlCambio;
use Modules\Inspeccion\Filament\Resources\ControlCambios\Pages\ListControlCambios;
use Modules\Inspeccion\Filament\Resources\ControlCambios\Schemas\ControlCambioForm;
use Modules\Inspeccion\Filament\Resources\ControlCambios\Tables\ControlCambiosTable;
use Modules\Inspeccion\Models\ControlCambio;

class ControlCambioResource extends Resource
{
    protected static ?string $model = ControlCambio::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'descripcion';

    public static function getModelLabel(): string
    {
        return __('inspeccion.control_cambio.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.control_cambio.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return ControlCambioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ControlCambiosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListControlCambios::route('/'),
            'board' => ControlCambiosBoard::route('/board'),
            'create' => CreateControlCambio::route('/create'),
            'edit' => EditControlCambio::route('/{record}/edit'),
        ];
    }
}
