<?php

namespace Modules\Inspeccion\Filament\Resources\TipoObservacions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\TipoObservacions\Pages\CreateTipoObservacion;
use Modules\Inspeccion\Filament\Resources\TipoObservacions\Pages\EditTipoObservacion;
use Modules\Inspeccion\Filament\Resources\TipoObservacions\Pages\ListTipoObservacions;
use Modules\Inspeccion\Filament\Resources\TipoObservacions\Schemas\TipoObservacionForm;
use Modules\Inspeccion\Filament\Resources\TipoObservacions\Tables\TipoObservacionsTable;
use Modules\Inspeccion\Models\TipoObservacion;

class TipoObservacionResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = TipoObservacion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_inspeccion_calidad');
    }

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.catalogos.tipo_observacion');
    }

    public static function form(Schema $schema): Schema
    {
        return TipoObservacionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TipoObservacionsTable::configure($table);
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
            'index' => ListTipoObservacions::route('/'),
            'create' => CreateTipoObservacion::route('/create'),
            'edit' => EditTipoObservacion::route('/{record}/edit'),
        ];
    }
}
