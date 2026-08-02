<?php

namespace Modules\Inspeccion\Filament\Resources\Observacions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Inspeccion\Filament\Resources\Observacions\Pages\CreateObservacion;
use Modules\Inspeccion\Filament\Resources\Observacions\Pages\EditObservacion;
use Modules\Inspeccion\Filament\Resources\Observacions\Pages\ListObservacions;
use Modules\Inspeccion\Filament\Resources\Observacions\Schemas\ObservacionForm;
use Modules\Inspeccion\Filament\Resources\Observacions\Tables\ObservacionsTable;
use Modules\Inspeccion\Models\Observacion;

/**
 * Único ítem del grupo de sidebar "Inspección" (ADR de reordenamiento):
 * el listado transversal de observaciones (todos los tableros, con
 * filtros por tablero/tipo/estado/especialidad ya existentes en
 * ObservacionsTable) es la vista que Calidad necesita mantener aunque
 * Tablero/VisitaInspeccion pasen a drilldown — ver el ADR para el porqué
 * de reusar este resource completo en vez de construir una página nueva
 * de solo lectura en paralelo.
 */
class ObservacionResource extends Resource
{
    protected static ?string $model = Observacion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'descripcion';

    public static function getNavigationGroup(): ?string
    {
        return __('inspeccion.navigation.grupo_inspeccion');
    }

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
