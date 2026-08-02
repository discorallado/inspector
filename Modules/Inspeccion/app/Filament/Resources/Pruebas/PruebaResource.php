<?php

namespace Modules\Inspeccion\Filament\Resources\Pruebas;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Resources\Pruebas\Pages\CreatePrueba;
use Modules\Inspeccion\Filament\Resources\Pruebas\Pages\EditPrueba;
use Modules\Inspeccion\Filament\Resources\Pruebas\Pages\ListPruebas;
use Modules\Inspeccion\Filament\Resources\Pruebas\RelationManagers\ItemsRelationManager;
use Modules\Inspeccion\Filament\Resources\Pruebas\Schemas\PruebaForm;
use Modules\Inspeccion\Filament\Resources\Pruebas\Tables\PruebasTable;
use Modules\Inspeccion\Models\Prueba;

/**
 * Sin ítem propio en el menú: se llega a una Prueba siempre desde su
 * Tablero (TableroResource > PruebasRelationManager) — antes se llegaba
 * desde VisitaInspeccion, cambió con el rename (ver ADR).
 */
class PruebaResource extends Resource
{
    protected static ?string $model = Prueba::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): string
    {
        return __('inspeccion.prueba.ejecucion.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.prueba.ejecucion.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return PruebaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PruebasTable::configure($table);
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
            'index' => ListPruebas::route('/'),
            'create' => CreatePrueba::route('/create'),
            'edit' => EditPrueba::route('/{record}/edit'),
        ];
    }
}
