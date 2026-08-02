<?php

namespace Modules\Inspeccion\Filament\Resources\ChecklistEjecucions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAInspeccionCalidad;
use Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\Pages\CreateChecklistEjecucion;
use Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\Pages\EditChecklistEjecucion;
use Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\Pages\ListChecklistEjecucions;
use Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\RelationManagers\ItemsRelationManager;
use Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\Schemas\ChecklistEjecucionForm;
use Modules\Inspeccion\Filament\Resources\ChecklistEjecucions\Tables\ChecklistEjecucionsTable;
use Modules\Inspeccion\Models\ChecklistEjecucion;

/**
 * Sin ítem propio en el menú del cluster: se llega a una ejecución de
 * checklist siempre desde la Visita de Inspección que la originó
 * (VisitaInspeccionResource > ChecklistEjecucionesRelationManager).
 */
class ChecklistEjecucionResource extends Resource
{
    use PerteneceAInspeccionCalidad;

    protected static ?string $model = ChecklistEjecucion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): string
    {
        return __('inspeccion.checklist.ejecucion.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.checklist.ejecucion.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return ChecklistEjecucionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChecklistEjecucionsTable::configure($table);
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
            'index' => ListChecklistEjecucions::route('/'),
            'create' => CreateChecklistEjecucion::route('/create'),
            'edit' => EditChecklistEjecucion::route('/{record}/edit'),
        ];
    }
}
