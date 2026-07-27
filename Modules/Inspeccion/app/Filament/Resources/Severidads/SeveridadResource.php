<?php

namespace Modules\Inspeccion\Filament\Resources\Severidads;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\Severidads\Pages\CreateSeveridad;
use Modules\Inspeccion\Filament\Resources\Severidads\Pages\EditSeveridad;
use Modules\Inspeccion\Filament\Resources\Severidads\Pages\ListSeveridads;
use Modules\Inspeccion\Filament\Resources\Severidads\Schemas\SeveridadForm;
use Modules\Inspeccion\Filament\Resources\Severidads\Tables\SeveridadsTable;
use Modules\Inspeccion\Models\Severidad;

class SeveridadResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = Severidad::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_inspeccion_calidad');
    }

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.catalogos.severidad');
    }

    public static function form(Schema $schema): Schema
    {
        return SeveridadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeveridadsTable::configure($table);
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
            'index' => ListSeveridads::route('/'),
            'create' => CreateSeveridad::route('/create'),
            'edit' => EditSeveridad::route('/{record}/edit'),
        ];
    }
}
