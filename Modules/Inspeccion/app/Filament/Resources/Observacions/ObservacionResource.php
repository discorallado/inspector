<?php

namespace Modules\Inspeccion\Filament\Resources\Observacions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Concerns\PerteneceAInspeccionCalidad;
use Modules\Inspeccion\Filament\Resources\Observacions\Pages\CreateObservacion;
use Modules\Inspeccion\Filament\Resources\Observacions\Pages\EditObservacion;
use Modules\Inspeccion\Filament\Resources\Observacions\Pages\ListObservacions;
use Modules\Inspeccion\Filament\Resources\Observacions\Schemas\ObservacionForm;
use Modules\Inspeccion\Filament\Resources\Observacions\Tables\ObservacionsTable;
use Modules\Inspeccion\Models\Observacion;

/**
 * Página por defecto del cluster Inspección de Calidad: el listado
 * transversal de observaciones/sugerencias/consultas es lo primero que
 * necesita ver un control de calidad (vencidas, pendientes críticas, etc.).
 */
class ObservacionResource extends Resource
{
    use PerteneceAInspeccionCalidad;

    protected static ?string $model = Observacion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'descripcion';

    public static function getModelLabel(): string
    {
        return __('inspeccion.observacion.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspeccion.observacion.plural');
    }

    /**
     * Cuenta las pendientes, no el total — es la cifra que le importa a
     * un control de calidad revisando el menú (mismo criterio que
     * ControlCambioResource::getNavigationBadge()).
     */
    public static function getNavigationBadge(): ?string
    {
        return (string) Observacion::query()
            ->whereHas('estadoObservacion', fn ($query) => $query->where('codigo', 'pendiente'))
            ->count();
    }

    public static function form(Schema $schema): Schema
    {
        return ObservacionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObservacionsTable::configure($table);
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
            'index' => ListObservacions::route('/'),
            'create' => CreateObservacion::route('/create'),
            'edit' => EditObservacion::route('/{record}/edit'),
        ];
    }
}
