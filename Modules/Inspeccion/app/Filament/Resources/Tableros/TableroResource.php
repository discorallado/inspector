<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\ActividadDetalle;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\CreateTablero;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\EditTablero;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\ListTableros;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\TableroActividadesResumen;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\TableroGanttChart;
use Modules\Inspeccion\Filament\Resources\Tableros\Pages\TableroKanbanBoard;
use Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers\ActividadesRelationManager;
use Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers\ControlCambiosRelationManager;
use Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers\HitosLegadosRelationManager;
use Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers\ObservacionesRelationManager;
use Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers\PruebasRelationManager;
use Modules\Inspeccion\Filament\Resources\Tableros\Schemas\TableroForm;
use Modules\Inspeccion\Filament\Resources\Tableros\Tables\TablerosTable;
use Modules\Inspeccion\Models\Tablero;

class TableroResource extends Resource
{
    protected static ?string $model = Tablero::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'tag';

    public static function getModelLabel(): string
    {
        return __('inspeccion.tablero.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.tablero.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return TableroForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TablerosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ActividadesRelationManager::class,
            ObservacionesRelationManager::class,
            PruebasRelationManager::class,
            ControlCambiosRelationManager::class,
            HitosLegadosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTableros::route('/'),
            'create' => CreateTablero::route('/create'),
            'edit' => EditTablero::route('/{record}/edit'),
            'kanban' => TableroKanbanBoard::route('/{record}/kanban'),
            'gantt' => TableroGanttChart::route('/{record}/gantt'),
            'actividad-detalle' => ActividadDetalle::route('/{record}/actividades/{actividadId}'),
            'actividades-resumen' => TableroActividadesResumen::route('/{record}/actividades-resumen'),
        ];
    }
}
