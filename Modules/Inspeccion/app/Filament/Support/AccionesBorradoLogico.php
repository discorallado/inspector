<?php

namespace Modules\Inspeccion\Filament\Support;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Model;

/**
 * Wiring estándar de Filament para modelos con SoftDeletes: filtro de
 * papelera + acciones de restaurar/eliminar definitivamente. Se usa en las
 * 4 entidades históricas del módulo (Visita, Observacion, ControlCambio,
 * ChecklistEjecucion) para que "eliminar" nunca sea la única opción.
 */
class AccionesBorradoLogico
{
    /**
     * @return array<int, mixed>
     */
    public static function filtros(): array
    {
        return [
            TrashedFilter::make(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function registro(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    public static function acciones(): BulkActionGroup
    {
        return BulkActionGroup::make([
            DeleteBulkAction::make(),
            RestoreBulkAction::make(),
            ForceDeleteBulkAction::make(),
        ]);
    }

    /**
     * EditAction no se oculta sola cuando el registro está soft-deleted
     * (a diferencia de DeleteAction). Sin esto, un registro "eliminado"
     * queda editable mientras está en la papelera.
     */
    public static function editar(): EditAction
    {
        return EditAction::make()->hidden(self::esTrashed(...));
    }

    public static function esTrashed(Model $record): bool
    {
        return method_exists($record, 'trashed') && $record->trashed();
    }
}
