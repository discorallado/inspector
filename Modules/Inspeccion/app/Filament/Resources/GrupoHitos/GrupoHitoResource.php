<?php

namespace Modules\Inspeccion\Filament\Resources\GrupoHitos;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\GrupoHitos\Pages\CreateGrupoHito;
use Modules\Inspeccion\Filament\Resources\GrupoHitos\Pages\EditGrupoHito;
use Modules\Inspeccion\Filament\Resources\GrupoHitos\Pages\ListGrupoHitos;
use Modules\Inspeccion\Filament\Resources\GrupoHitos\Schemas\GrupoHitoForm;
use Modules\Inspeccion\Filament\Resources\GrupoHitos\Tables\GrupoHitosTable;
use Modules\Inspeccion\Models\GrupoHito;

class GrupoHitoResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = GrupoHito::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_seguimiento_tableros');
    }

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.catalogos.grupo_hito');
    }

    public static function form(Schema $schema): Schema
    {
        return GrupoHitoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GrupoHitosTable::configure($table);
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
            'index' => ListGrupoHitos::route('/'),
            'create' => CreateGrupoHito::route('/create'),
            'edit' => EditGrupoHito::route('/{record}/edit'),
        ];
    }
}
