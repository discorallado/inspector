<?php

namespace Modules\Inspeccion\Filament\Resources\VisitaInspeccions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAInspeccionCalidad;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Pages\CreateVisitaInspeccion;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Pages\EditVisitaInspeccion;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Pages\ListVisitaInspeccions;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\RelationManagers\ChecklistEjecucionesRelationManager;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\RelationManagers\ObservacionesRelationManager;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Schemas\VisitaInspeccionForm;
use Modules\Inspeccion\Filament\Resources\VisitaInspeccions\Tables\VisitaInspeccionsTable;
use Modules\Inspeccion\Models\VisitaInspeccion;

class VisitaInspeccionResource extends Resource
{
    use PerteneceAInspeccionCalidad;

    protected static ?string $model = VisitaInspeccion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 2;

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
            ChecklistEjecucionesRelationManager::class,
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
