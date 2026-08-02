<?php

namespace Modules\Inspeccion\Filament\Resources\Proyectos;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Resources\Proyectos\Pages\CreateProyecto;
use Modules\Inspeccion\Filament\Resources\Proyectos\Pages\EditProyecto;
use Modules\Inspeccion\Filament\Resources\Proyectos\Pages\ListProyectos;
use Modules\Inspeccion\Filament\Resources\Proyectos\RelationManagers\VisitasRelationManager;
use Modules\Inspeccion\Filament\Resources\Proyectos\Schemas\ProyectoForm;
use Modules\Inspeccion\Filament\Resources\Proyectos\Tables\ProyectosTable;
use Modules\Inspeccion\Models\Proyecto;

/**
 * Nav reactivada (ADR de reordenamiento de sidebar): Proyecto pasa a ser
 * el primer nivel de drilldown (Proyecto -> Tablero, Proyecto -> Visitas),
 * ya no es un stub oculto — sigue siendo un modelo mínimo (CLAUDE.md §3),
 * eso no cambió.
 */
class ProyectoResource extends Resource
{
    protected static ?string $model = Proyecto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getModelLabel(): string
    {
        return __('inspeccion.proyecto.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.proyecto.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return ProyectoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProyectosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VisitasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProyectos::route('/'),
            'create' => CreateProyecto::route('/create'),
            'edit' => EditProyecto::route('/{record}/edit'),
        ];
    }
}
