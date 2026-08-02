<?php

namespace Modules\Inspeccion\Filament\Resources\GrupoHitoLegados;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\GrupoHitoLegados\Pages\CreateGrupoHitoLegado;
use Modules\Inspeccion\Filament\Resources\GrupoHitoLegados\Pages\EditGrupoHitoLegado;
use Modules\Inspeccion\Filament\Resources\GrupoHitoLegados\Pages\ListGrupoHitosLegados;
use Modules\Inspeccion\Filament\Resources\GrupoHitoLegados\Schemas\GrupoHitoLegadoForm;
use Modules\Inspeccion\Filament\Resources\GrupoHitoLegados\Tables\GrupoHitoLegadosTable;
use Modules\Inspeccion\Models\GrupoHitoLegado;

class GrupoHitoLegadoResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = GrupoHitoLegado::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_seguimiento_tableros');
    }

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.catalogos.grupo_hito_legado');
    }

    public static function form(Schema $schema): Schema
    {
        return GrupoHitoLegadoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GrupoHitoLegadosTable::configure($table);
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
            'index' => ListGrupoHitosLegados::route('/'),
            'create' => CreateGrupoHitoLegado::route('/create'),
            'edit' => EditGrupoHitoLegado::route('/{record}/edit'),
        ];
    }
}
