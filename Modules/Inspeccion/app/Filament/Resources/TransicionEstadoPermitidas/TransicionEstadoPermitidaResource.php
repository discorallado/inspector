<?php

namespace Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Pages\CreateTransicionEstadoPermitida;
use Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Pages\EditTransicionEstadoPermitida;
use Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Pages\ListTransicionEstadoPermitidas;
use Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Schemas\TransicionEstadoPermitidaForm;
use Modules\Inspeccion\Filament\Resources\TransicionEstadoPermitidas\Tables\TransicionEstadoPermitidasTable;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;

class TransicionEstadoPermitidaResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = TransicionEstadoPermitida::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_maquina_estados');
    }

    protected static ?string $recordTitleAttribute = 'tipo_catalogo';

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.catalogos.transicion_estado_permitida');
    }

    public static function form(Schema $schema): Schema
    {
        return TransicionEstadoPermitidaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransicionEstadoPermitidasTable::configure($table);
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
            'index' => ListTransicionEstadoPermitidas::route('/'),
            'create' => CreateTransicionEstadoPermitida::route('/create'),
            'edit' => EditTransicionEstadoPermitida::route('/{record}/edit'),
        ];
    }
}
