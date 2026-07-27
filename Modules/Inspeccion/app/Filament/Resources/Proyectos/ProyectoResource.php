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
use Modules\Inspeccion\Filament\Resources\Proyectos\Schemas\ProyectoForm;
use Modules\Inspeccion\Filament\Resources\Proyectos\Tables\ProyectosTable;
use Modules\Inspeccion\Models\Proyecto;

/**
 * Sin ítem propio en el menú: Proyecto es un stub de un campo (ver
 * CLAUDE.md §3) y se crea inline desde el Select de "Proyecto" en los
 * formularios de Tablero y VisitaInspeccion. El recurso sigue existiendo
 * por si hace falta editarlo/eliminarlo directamente.
 */
class ProyectoResource extends Resource
{
    protected static ?string $model = Proyecto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

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
            //
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
