<?php

namespace Modules\Inspeccion\Filament\Resources\Especialidads;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAConfiguracion;
use Modules\Inspeccion\Filament\Resources\Especialidads\Pages\CreateEspecialidad;
use Modules\Inspeccion\Filament\Resources\Especialidads\Pages\EditEspecialidad;
use Modules\Inspeccion\Filament\Resources\Especialidads\Pages\ListEspecialidads;
use Modules\Inspeccion\Filament\Resources\Especialidads\Schemas\EspecialidadForm;
use Modules\Inspeccion\Filament\Resources\Especialidads\Tables\EspecialidadsTable;
use Modules\Inspeccion\Models\Especialidad;

class EspecialidadResource extends Resource
{
    use PerteneceAConfiguracion;

    protected static ?string $model = Especialidad::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_inspeccion_calidad');
    }

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.catalogos.especialidad');
    }

    public static function form(Schema $schema): Schema
    {
        return EspecialidadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EspecialidadsTable::configure($table);
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
            'index' => ListEspecialidads::route('/'),
            'create' => CreateEspecialidad::route('/create'),
            'edit' => EditEspecialidad::route('/{record}/edit'),
        ];
    }
}
