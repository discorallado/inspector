<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistItemLibraries;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\ChecklistItemLibraries\Pages\CreateChecklistItemLibrary;
use Modules\Inspeccion\Filament\Resources\ChecklistItemLibraries\Pages\EditChecklistItemLibrary;
use Modules\Inspeccion\Filament\Resources\ChecklistItemLibraries\Pages\ListChecklistItemLibraries;
use Modules\Inspeccion\Filament\Resources\ChecklistItemLibraries\Schemas\ChecklistItemLibraryForm;
use Modules\Inspeccion\Filament\Resources\ChecklistItemLibraries\Tables\ChecklistItemLibrariesTable;
use Modules\Inspeccion\Models\ChecklistItemLibrary;

class ChecklistItemLibraryResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = ChecklistItemLibrary::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_inspeccion_calidad');
    }

    protected static ?string $recordTitleAttribute = 'item';

    public static function getModelLabel(): string
    {
        return __('inspeccion.checklist.item_library.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.checklist.item_library.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return ChecklistItemLibraryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChecklistItemLibrariesTable::configure($table);
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
            'index' => ListChecklistItemLibraries::route('/'),
            'create' => CreateChecklistItemLibrary::route('/create'),
            'edit' => EditChecklistItemLibrary::route('/{record}/edit'),
        ];
    }
}
