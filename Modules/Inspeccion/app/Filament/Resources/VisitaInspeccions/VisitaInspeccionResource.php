<?php

namespace Modules\Inspeccion\Filament\Resources\VisitaInspeccions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Pages\CreateVisitaInspeccion;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Pages\EditVisitaInspeccion;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Pages\ListVisitaInspeccions;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\RelationManagers\ObservacionesRelationManager;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Schemas\VisitaInspeccionForm;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Tables\VisitaInspeccionsTable;
use Modules\Inspeccion\Models\VisitaInspeccion;

/**
 * Sin ítem propio en el menú: se llega a una Visita siempre desde su
 * Proyecto (ProyectoResource > VisitasRelationManager) — ver ADR del
 * reordenamiento de sidebar.
 */
class VisitaInspeccionResource extends Resource
{
    protected static ?string $model = VisitaInspeccion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'fecha';

    public static function getModelLabel(): string
    {
        return __('inspeccion.visita_inspeccion.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.visita_inspeccion.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return VisitaInspeccionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisitaInspeccionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ObservacionesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisitaInspeccions::route('/'),
            'create' => CreateVisitaInspeccion::route('/create'),
            'edit' => EditVisitaInspeccion::route('/{record}/edit'),
        ];
    }
}
