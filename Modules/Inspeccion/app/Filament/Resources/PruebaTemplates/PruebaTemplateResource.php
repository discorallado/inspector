<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistTemplates;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\ChecklistTemplates\Pages\CreateChecklistTemplate;
use Modules\Inspeccion\Filament\Resources\ChecklistTemplates\Pages\EditChecklistTemplate;
use Modules\Inspeccion\Filament\Resources\ChecklistTemplates\Pages\ListChecklistTemplates;
use Modules\Inspeccion\Filament\Resources\ChecklistTemplates\RelationManagers\ItemsRelationManager;
use Modules\Inspeccion\Filament\Resources\ChecklistTemplates\Schemas\ChecklistTemplateForm;
use Modules\Inspeccion\Filament\Resources\ChecklistTemplates\Tables\ChecklistTemplatesTable;
use Modules\Inspeccion\Models\ChecklistTemplate;

class ChecklistTemplateResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = ChecklistTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_inspeccion_calidad');
    }

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getModelLabel(): string
    {
        return __('inspeccion.checklist.template.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.checklist.template.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return ChecklistTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChecklistTemplatesTable::configure($table);
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
            'index' => ListChecklistTemplates::route('/'),
            'create' => CreateChecklistTemplate::route('/create'),
            'edit' => EditChecklistTemplate::route('/{record}/edit'),
        ];
    }
}
