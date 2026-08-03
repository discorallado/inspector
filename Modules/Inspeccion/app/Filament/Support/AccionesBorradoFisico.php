<?php

namespace Modules\Inspeccion\Filament\Support;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Tablero, Proyecto (sin SoftDeletes) y el forceDelete() de Actividad/Tarea
 * (con SoftDeletes, pero el borrado definitivo también es físico) dependen
 * de la FK real de la BD (RESTRICT, no CASCADE): si quedan registros hijos
 * colgando, el delete debe fallar (ver RestriccionBorradoFisicoTest, que
 * confirma esto como comportamiento esperado). Ni DeleteAction::make() de
 * Filament ni las Actions montadas a mano del árbol atrapan esa
 * QueryException por defecto — sin este wrapper, el usuario ve un error
 * 500 con el stack trace de SQL en vez de un aviso.
 */
class AccionesBorradoFisico
{
    public static function eliminar(): DeleteAction
    {
        return DeleteAction::make()
            ->action(function (Model $record): void {
                if (! static::intentar(fn () => $record->delete())) {
                    return;
                }

                Notification::make()
                    ->success()
                    ->title(__('filament-actions::delete.single.notifications.deleted.title'))
                    ->send();
            });
    }

    /**
     * Para usar dentro de Actions montadas a mano (ver
     * ActividadesRelationManager::eliminarDefinitivo*Action()) donde no
     * aplica el factory eliminar() de arriba. Devuelve false (y ya mandó
     * la notificación de bloqueo) si el borrado se rechazó por la FK —
     * el llamador debe cortar ahí y no mandar su propia notificación de
     * éxito.
     */
    public static function intentar(callable $eliminar): bool
    {
        try {
            $eliminar();
        } catch (QueryException $exception) {
            // 23000 = SQLSTATE de integrity constraint violation, portable
            // entre MariaDB/MySQL/SQLite/Postgres. Solo se intercepta este
            // código — cualquier otro error de BD sigue reventando normal,
            // no hay que enmascarar bugs reales.
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            Notification::make()
                ->danger()
                ->title(__('inspeccion.borrado_fisico.bloqueado'))
                ->body(__('inspeccion.borrado_fisico.bloqueado_detalle'))
                ->send();

            return false;
        }

        return true;
    }
}
