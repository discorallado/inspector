<?php

namespace Modules\Inspeccion\Filament\Resources\PruebaItemLibraries;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\PruebaItemLibraries\Pages\CreatePruebaItemLibrary;
use Modules\Inspeccion\Filament\Resources\PruebaItemLibraries\Pages\EditPruebaItemLibrary;
use Modules\Inspeccion\Filament\Resources\PruebaItemLibraries\Pages\ListPruebaItemLibraries;
use Modules\Inspeccion\Filament\Resources\PruebaItemLibraries\Schemas\PruebaItemLibraryForm;
use Modules\Inspeccion\Filament\Resources\PruebaItemLibraries\Tables\PruebaItemLibrariesTable;
use Modules\Inspeccion\Models\PruebaItemLibrary;

class PruebaItemLibraryResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = PruebaItemLibrary::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_inspeccion_calidad');
    }

    protected static ?string $recordTitleAttribute = 'item';

    public static function getModelLabel(): string
    {
        return __('inspeccion.prueba.item_library.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.prueba.item_library.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return PruebaItemLibraryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PruebaItemLibrariesTable::configure($table);
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
            'index' => ListPruebaItemLibraries::route('/'),
            'create' => CreatePruebaItemLibrary::route('/create'),
            'edit' => EditPruebaItemLibrary::route('/{record}/edit'),
        ];
    }
}
