<?php

namespace Modules\Inspeccion\Filament\Resources\EstadoObservacions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\EstadoObservacions\Pages\CreateEstadoObservacion;
use Modules\Inspeccion\Filament\Resources\EstadoObservacions\Pages\EditEstadoObservacion;
use Modules\Inspeccion\Filament\Resources\EstadoObservacions\Pages\ListEstadoObservacions;
use Modules\Inspeccion\Filament\Resources\EstadoObservacions\Schemas\EstadoObservacionForm;
use Modules\Inspeccion\Filament\Resources\EstadoObservacions\Tables\EstadoObservacionsTable;
use Modules\Inspeccion\Models\EstadoObservacion;

class EstadoObservacionResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = EstadoObservacion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_inspeccion_calidad');
    }

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.catalogos.estado_observacion');
    }

    public static function form(Schema $schema): Schema
    {
        return EstadoObservacionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EstadoObservacionsTable::configure($table);
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
            'index' => ListEstadoObservacions::route('/'),
            'create' => CreateEstadoObservacion::route('/create'),
            'edit' => EditEstadoObservacion::route('/{record}/edit'),
        ];
    }
}
