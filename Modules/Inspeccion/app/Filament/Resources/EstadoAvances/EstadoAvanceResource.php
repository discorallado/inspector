<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoAvances;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\EstadoAvances\Pages\CreateEstadoAvance;
use Modules\Inspeccion\Filament\Resources\EstadoAvances\Pages\EditEstadoAvance;
use Modules\Inspeccion\Filament\Resources\EstadoAvances\Pages\ListEstadoAvances;
use Modules\Inspeccion\Filament\Resources\EstadoAvances\Schemas\EstadoAvanceForm;
use Modules\Inspeccion\Filament\Resources\EstadoAvances\Tables\EstadoAvancesTable;
use Modules\Inspeccion\Models\EstadoAvance;

class EstadoAvanceResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = EstadoAvance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_seguimiento_tableros');
    }

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.catalogos.estado_avance');
    }

    public static function form(Schema $schema): Schema
    {
        return EstadoAvanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EstadoAvancesTable::configure($table);
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
            'index' => ListEstadoAvances::route('/'),
            'create' => CreateEstadoAvance::route('/create'),
            'edit' => EditEstadoAvance::route('/{record}/edit'),
        ];
    }
}
