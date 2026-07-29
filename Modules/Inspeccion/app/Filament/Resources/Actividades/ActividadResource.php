<?php

namespace Modules\Inspeccion\Filament\Resources\Actividades;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Resources\Actividades\Pages\EditActividad;
use Modules\Inspeccion\Filament\Resources\Actividades\Pages\ListActividades;
use Modules\Inspeccion\Filament\Resources\Actividades\RelationManagers\TareasRelationManager;
use Modules\Inspeccion\Filament\Resources\Actividades\Schemas\ActividadForm;
use Modules\Inspeccion\Filament\Resources\Actividades\Tables\ActividadesTable;
use Modules\Inspeccion\Models\Actividad;

/**
 * Sin entrada propia en el menú de navegación: Actividad se gestiona desde
 * ActividadesRelationManager de TableroResource, que enlaza a la página de
 * edición de acá para administrar sus Tareas (Actividad::tareas() es un
 * HasMany real, a diferencia de Tablero::tareas() que es un HasManyThrough
 * sin soporte de create() — por eso el CRUD de Tarea vive acá y no en un
 * relation manager anidado bajo Tablero).
 */
class ActividadResource extends Resource
{
    protected static ?string $model = Actividad::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return __('inspeccion.actividad.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.actividad.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return ActividadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActividadesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TareasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActividades::route('/'),
            'edit' => EditActividad::route('/{record}/edit'),
        ];
    }
}
