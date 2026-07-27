<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoCambios;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\EstadoCambios\Pages\CreateEstadoCambio;
use Modules\Inspeccion\Filament\Resources\EstadoCambios\Pages\EditEstadoCambio;
use Modules\Inspeccion\Filament\Resources\EstadoCambios\Pages\ListEstadoCambios;
use Modules\Inspeccion\Filament\Resources\EstadoCambios\Schemas\EstadoCambioForm;
use Modules\Inspeccion\Filament\Resources\EstadoCambios\Tables\EstadoCambiosTable;
use Modules\Inspeccion\Models\EstadoCambio;

class EstadoCambioResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = EstadoCambio::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_control_cambios');
    }

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.catalogos.estado_cambio');
    }

    public static function form(Schema $schema): Schema
    {
        return EstadoCambioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EstadoCambiosTable::configure($table);
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
            'index' => ListEstadoCambios::route('/'),
            'create' => CreateEstadoCambio::route('/create'),
            'edit' => EditEstadoCambio::route('/{record}/edit'),
        ];
    }
}
