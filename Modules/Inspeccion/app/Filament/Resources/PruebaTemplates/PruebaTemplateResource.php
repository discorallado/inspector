<?php

namespace Modules\Inspeccion\Filament\Resources\PruebaTemplates;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\PruebaTemplates\Pages\CreatePruebaTemplate;
use Modules\Inspeccion\Filament\Resources\PruebaTemplates\Pages\EditPruebaTemplate;
use Modules\Inspeccion\Filament\Resources\PruebaTemplates\Pages\ListPruebaTemplates;
use Modules\Inspeccion\Filament\Resources\PruebaTemplates\RelationManagers\ItemsRelationManager;
use Modules\Inspeccion\Filament\Resources\PruebaTemplates\Schemas\PruebaTemplateForm;
use Modules\Inspeccion\Filament\Resources\PruebaTemplates\Tables\PruebaTemplatesTable;
use Modules\Inspeccion\Models\PruebaTemplate;

class PruebaTemplateResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = PruebaTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_inspeccion_calidad');
    }

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getModelLabel(): string
    {
        return __('inspeccion.prueba.template.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.prueba.template.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return PruebaTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PruebaTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPruebaTemplates::route('/'),
            'create' => CreatePruebaTemplate::route('/create'),
            'edit' => EditPruebaTemplate::route('/{record}/edit'),
        ];
    }
}
