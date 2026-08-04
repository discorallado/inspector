<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\RelationManagers;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inspeccion\Filament\Resources\Tableros\Schemas\ActividadForm;
use Modules\Inspeccion\Filament\Resources\Tableros\Schemas\TareaForm;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;
use Modules\Inspeccion\Filament\Support\AccionesBorradoFisico;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Services\TareaDependencyService;

/**
 * Reemplaza la tabla plana + drill-down a ActividadResource (ver ADR de este
 * cambio): Actividades y sus Tareas se ven y se editan en un árbol embebido
 * acá mismo, sin salir nunca del Tablero. No es un RelationManager con
 * tabla — se sobreescribe $view y todo el CRUD corre por Actions montadas a
 * mano (mountAction con argumentos), porque el árbol mezcla dos modelos
 * (Actividad y Tarea) que Filament\Tables\Table no puede representar en una
 * sola tabla.
 *
 * ActividadResource (y su TareasRelationManager anidado) quedó retirado:
 * este árbol cubre el mismo CRUD sin la navegación de página completa que
 * tenía el botón "Ver Tareas".
 *
 * Port parcial de axon (app/Livewire/ActivityAccordion.php): reorder
 * drag-and-drop, insertar tarea en posición, agendar fechas desde la
 * anterior, predecesoras en el modal y notificaciones. Deliberadamente NO
 * portado (ver ADR): asignados, comentarios, adjuntos — necesitan
 * infraestructura nueva (tabla pivot, paquete filament-comments) y quedan
 * para una ronda aparte con su propio /arquitecto.
 */
class ActividadesRelationManager extends RelationManager
{
    protected static string $relationship = 'actividades';

    protected string $view = 'inspeccion::filament.resources.tableros.relation-managers.actividades-arbol';

    public bool $mostrarEliminados = false;

    public function form(Schema $schema): Schema
    {
        return ActividadForm::configure($schema);
    }

    /**
     * @return Collection<int, Actividad>
     */
    public function getActividades(): Collection
    {
        return Actividad::query()
            ->where('tablero_id', $this->getOwnerRecord()->id)
            ->when($this->mostrarEliminados, fn ($query) => $query->withTrashed())
            ->with(['tareas' => fn ($query) => $this->mostrarEliminados ? $query->withTrashed() : $query])
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    public function toggleMostrarEliminados(): void
    {
        $this->mostrarEliminados = ! $this->mostrarEliminados;
    }

    protected function resolveActividad(array $arguments): Actividad
    {
        return Actividad::withTrashed()->findOrFail($arguments['id']);
    }

    protected function resolveTarea(array $arguments): Tarea
    {
        return Tarea::withTrashed()->findOrFail($arguments['id']);
    }

    // -------------------------------------------------------------------
    // Reorder (drag-and-drop, ver actividades-arbol.blade.php)
    // -------------------------------------------------------------------

    /**
     * @param  list<int>  $orderedIds
     */
    public function reordenarActividades(array $orderedIds): void
    {
        $this->authorize('update', $this->getOwnerRecord());

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                $actividad = Actividad::query()
                    ->where('id', $id)
                    ->where('tablero_id', $this->getOwnerRecord()->id)
                    ->first();

                if (! $actividad) {
                    continue;
                }

                $actividad->update(['orden' => $index + 1]);
                $this->recalcularCodesDeActividad($actividad);
            }
        });
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reordenarTareas(array $orderedIds, int $actividadId): void
    {
        $this->authorize('update', $this->getOwnerRecord());

        // La Actividad destino tiene que ser de ESTE tablero — el drag
        // permite soltar en cualquier lista de tarea visible en pantalla,
        // pero el árbol solo debería mostrar Actividades del tablero
        // actual. Sin este chequeo, un actividadId ajeno pasaría igual el
        // ->where('id', ...) de abajo (mismo hallazgo de /revisor que en
        // TableroGanttChart::persistirOrden()).
        $actividad = Actividad::query()
            ->where('id', $actividadId)
            ->where('tablero_id', $this->getOwnerRecord()->id)
            ->first();

        if (! $actividad) {
            return;
        }

        $tag = $this->getOwnerRecord()->tag;

        // Ids validados de antemano (pertenecen a este Tablero) — hace
        // falta la lista fija antes de tocar nada porque el UPDATE de
        // fase 1 de abajo ya no puede filtrar por whereHas('actividad',
        // tablero_id) fila por fila una vez que empezamos a mover
        // actividad_id.
        $idsValidos = Tarea::query()
            ->whereIn('id', $orderedIds)
            ->whereHas('actividad', fn ($query) => $query->where('tablero_id', $this->getOwnerRecord()->id))
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($orderedIds, $idsValidos, $actividad, $tag): void {
            // Fase 1: code temporal único por id (nunca puede chocar con
            // unique(actividad_id, code), que es real en BD) antes de
            // reasignar los codes definitivos en fase 2. Sin esto, mover
            // una Tarea a una posición cuyo code todavía ocupa otra Tarea
            // sin procesar en este mismo loop revienta la transacción
            // entera con un QueryException 23000 (hallazgo de /revisor).
            foreach ($idsValidos as $id) {
                Tarea::query()->where('id', $id)->update(['code' => "__tmp_reorden_{$id}__"]);
            }

            foreach ($orderedIds as $index => $id) {
                if (! in_array($id, $idsValidos, true)) {
                    continue;
                }

                $orden = $index + 1;

                Tarea::query()->where('id', $id)->update([
                    'actividad_id' => $actividad->id,
                    'orden' => $orden,
                    'code' => Tarea::generarCode($tag, $actividad->orden, $orden),
                ]);
            }
        });
    }

    /**
     * Recalcula el `code` de todas las Tareas de una Actividad — hace
     * falta cada vez que actividad.orden cambia (reordenarActividades),
     * porque el code embebe ese número. updateQuietly: no dispara
     * TareaObserver, no estamos tocando status.
     */
    protected function recalcularCodesDeActividad(Actividad $actividad): void
    {
        $tag = $this->getOwnerRecord()->tag;

        $actividad->tareas()->get()->each(
            fn (Tarea $tarea) => $tarea->updateQuietly(['code' => Tarea::generarCode($tag, $actividad->orden, $tarea->orden)])
        );
    }

    // -------------------------------------------------------------------
    // Actividad
    // -------------------------------------------------------------------

    public function crearActividadAction(): Action
    {
        return Action::make('crearActividad')
            ->label(__('inspeccion.actividad.arbol.nueva_actividad'))
            ->icon(Heroicon::OutlinedPlus)
            ->authorize('create', Actividad::class)
            ->schema(fn (Schema $schema) => ActividadForm::configure($schema))
            ->action(function (array $data): void {
                $this->getOwnerRecord()->actividades()->create($data);

                Notification::make()->success()->title(__('inspeccion.actividad.arbol.notificaciones.creada'))->send();
            });
    }

    public function urlDetalleActividad(Actividad $actividad): string
    {
        return TableroResource::getUrl('actividad-detalle', [
            'record' => $this->getOwnerRecord(),
            'actividadId' => $actividad->id,
        ]);
    }

    public function editarActividadAction(): Action
    {
        return Action::make('editarActividad')
            ->label(__('inspeccion.actividad.arbol.editar'))
            ->icon(Heroicon::OutlinedPencil)
            ->record(fn (array $arguments) => $this->resolveActividad($arguments))
            ->authorize('update')
            ->schema(fn (Schema $schema) => ActividadForm::configure($schema))
            ->fillForm(fn (array $arguments): array => $this->resolveActividad($arguments)->toArray())
            ->action(function (array $arguments, array $data): void {
                $this->resolveActividad($arguments)->update($data);

                Notification::make()->success()->title(__('inspeccion.actividad.arbol.notificaciones.actualizada'))->send();
            });
    }

    public function eliminarActividadAction(): Action
    {
        return Action::make('eliminarActividad')
            ->label(__('inspeccion.actividad.arbol.eliminar'))
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->record(fn (array $arguments) => $this->resolveActividad($arguments))
            ->authorize('delete')
            ->action(function (array $arguments): void {
                $this->resolveActividad($arguments)->delete();

                Notification::make()->danger()->title(__('inspeccion.actividad.arbol.notificaciones.eliminada'))->send();
            });
    }

    public function restaurarActividadAction(): Action
    {
        return Action::make('restaurarActividad')
            ->label(__('inspeccion.actividad.arbol.restaurar'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->record(fn (array $arguments) => $this->resolveActividad($arguments))
            ->authorize('restore')
            ->action(function (array $arguments): void {
                $this->resolveActividad($arguments)->restore();

                Notification::make()->success()->title(__('inspeccion.actividad.arbol.notificaciones.restaurada'))->send();
            });
    }

    public function eliminarDefinitivoActividadAction(): Action
    {
        return Action::make('eliminarDefinitivoActividad')
            ->label(__('inspeccion.actividad.arbol.eliminar_definitivo'))
            ->icon(Heroicon::OutlinedXMark)
            ->color('danger')
            ->requiresConfirmation()
            ->record(fn (array $arguments) => $this->resolveActividad($arguments))
            ->authorize('forceDelete')
            ->action(function (array $arguments): void {
                if (! AccionesBorradoFisico::intentar(fn () => $this->resolveActividad($arguments)->forceDelete())) {
                    return;
                }

                Notification::make()->danger()->title(__('inspeccion.actividad.arbol.notificaciones.eliminada_definitivo'))->send();
            });
    }

    // -------------------------------------------------------------------
    // Tarea
    // -------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extraerPredecessors(array &$data): array
    {
        $predecessors = $data['predecessors'] ?? [];
        unset($data['predecessors']);

        return $predecessors;
    }

    protected function sincronizarPredecessorsYNotificar(Tarea $tarea, array $predecessors): void
    {
        $omitidas = TareaDependencyService::syncPredecessors($tarea, $predecessors);

        if ($omitidas !== []) {
            Notification::make()->warning()->title(__('inspeccion.tarea.arbol.ciclo_omitido'))->send();
        }
    }

    public function crearTareaAction(): Action
    {
        return Action::make('crearTarea')
            ->label(__('inspeccion.tarea.arbol.nueva_tarea'))
            ->icon(Heroicon::OutlinedPlus)
            ->authorize('create', Tarea::class)
            ->schema(fn (Schema $schema) => TareaForm::configure($schema))
            ->fillForm(fn (array $arguments): array => ['actividad_id' => $arguments['actividadId']])
            ->action(function (array $data): void {
                $predecessors = $this->extraerPredecessors($data);
                $actividad = Actividad::findOrFail($data['actividad_id']);
                $data['orden'] = ($actividad->tareas()->max('orden') ?? 0) + 1;
                $data['code'] = Tarea::generarCode($this->getOwnerRecord()->tag, $actividad->orden, $data['orden']);

                $tarea = Tarea::create($data);
                $this->sincronizarPredecessorsYNotificar($tarea, $predecessors);

                Notification::make()->success()->title(__('inspeccion.tarea.arbol.notificaciones.creada'))->send();
            });
    }

    public function editarTareaAction(): Action
    {
        return Action::make('editarTarea')
            ->label(__('inspeccion.tarea.arbol.editar'))
            ->icon(Heroicon::OutlinedPencil)
            ->record(fn (array $arguments) => $this->resolveTarea($arguments))
            ->authorize('update')
            ->schema(fn (Schema $schema, array $arguments) => TareaForm::configure($schema, $arguments['id'] ?? null))
            ->fillForm(fn (array $arguments): array => [
                ...$this->resolveTarea($arguments)->toArray(),
                'predecessors' => $this->resolveTarea($arguments)->predecessors->pluck('id')->all(),
            ])
            ->action(function (array $arguments, array $data): void {
                $predecessors = $this->extraerPredecessors($data);
                $tarea = $this->resolveTarea($arguments);
                $tarea->update($data);
                $this->sincronizarPredecessorsYNotificar($tarea, $predecessors);

                Notification::make()->success()->title(__('inspeccion.tarea.arbol.notificaciones.actualizada'))->send();
            });
    }

    public function eliminarTareaAction(): Action
    {
        return Action::make('eliminarTarea')
            ->label(__('inspeccion.tarea.arbol.eliminar'))
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->record(fn (array $arguments) => $this->resolveTarea($arguments))
            ->authorize('delete')
            ->action(function (array $arguments): void {
                $this->resolveTarea($arguments)->delete();

                Notification::make()->danger()->title(__('inspeccion.tarea.arbol.notificaciones.eliminada'))->send();
            });
    }

    public function restaurarTareaAction(): Action
    {
        return Action::make('restaurarTarea')
            ->label(__('inspeccion.tarea.arbol.restaurar'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->record(fn (array $arguments) => $this->resolveTarea($arguments))
            ->authorize('restore')
            ->action(function (array $arguments): void {
                $this->resolveTarea($arguments)->restore();

                Notification::make()->success()->title(__('inspeccion.tarea.arbol.notificaciones.restaurada'))->send();
            });
    }

    public function eliminarDefinitivoTareaAction(): Action
    {
        return Action::make('eliminarDefinitivoTarea')
            ->label(__('inspeccion.tarea.arbol.eliminar_definitivo'))
            ->icon(Heroicon::OutlinedXMark)
            ->color('danger')
            ->requiresConfirmation()
            ->record(fn (array $arguments) => $this->resolveTarea($arguments))
            ->authorize('forceDelete')
            ->action(function (array $arguments): void {
                if (! AccionesBorradoFisico::intentar(fn () => $this->resolveTarea($arguments)->forceDelete())) {
                    return;
                }

                Notification::make()->danger()->title(__('inspeccion.tarea.arbol.notificaciones.eliminada_definitivo'))->send();
            });
    }

    /**
     * Portado de axon (ActivityAccordion::insertTaskAction()): crea una
     * Tarea nueva antes/después de $arguments['id'], corriendo el orden de
     * las siguientes para hacerle espacio.
     */
    public function insertarTareaAction(): Action
    {
        return Action::make('insertarTarea')
            ->label(fn (array $arguments) => ($arguments['position'] ?? 'after') === 'before'
                ? __('inspeccion.tarea.arbol.insertar_antes')
                : __('inspeccion.tarea.arbol.insertar_despues'))
            ->icon(Heroicon::OutlinedPlusCircle)
            ->authorize('create', Tarea::class)
            ->schema(fn (Schema $schema) => TareaForm::configure($schema))
            ->fillForm(fn (array $arguments): array => ['actividad_id' => $this->resolveTarea($arguments)->actividad_id])
            ->action(function (array $data, array $arguments): void {
                $referencia = $this->resolveTarea($arguments);
                $actividad = $referencia->actividad;
                $position = $arguments['position'] ?? 'after';
                $nuevoOrden = $position === 'before' ? $referencia->orden : $referencia->orden + 1;
                $tag = $this->getOwnerRecord()->tag;

                DB::transaction(function () use ($referencia, $nuevoOrden, $actividad, $tag, &$data): void {
                    // increment() + code embebe el orden -> hay que
                    // recorrer una por una para recalcular el code de
                    // cada Tarea corrida, no solo su número. Dos fases
                    // (code temporal único por id, después el code
                    // definitivo) para no chocar contra el
                    // unique(actividad_id, code) real de BD: sin esto, con
                    // 2+ Tareas corridas la primera fila procesada pisaba
                    // el code todavía vigente de la siguiente y la
                    // transacción entera reventaba con un QueryException
                    // 23000 (hallazgo de /revisor).
                    $tareasAfectadas = Tarea::query()
                        ->where('actividad_id', $referencia->actividad_id)
                        ->where('orden', '>=', $nuevoOrden)
                        ->get();

                    $tareasAfectadas->each(
                        fn (Tarea $tarea) => $tarea->updateQuietly(['code' => "__tmp_insertar_{$tarea->id}__"])
                    );

                    $tareasAfectadas->each(function (Tarea $tarea) use ($actividad, $tag): void {
                        $tarea->updateQuietly([
                            'orden' => $tarea->orden + 1,
                            'code' => Tarea::generarCode($tag, $actividad->orden, $tarea->orden + 1),
                        ]);
                    });

                    $predecessors = $this->extraerPredecessors($data);
                    $data['orden'] = $nuevoOrden;
                    $data['code'] = Tarea::generarCode($tag, $actividad->orden, $nuevoOrden);
                    $tarea = Tarea::create($data);
                    $this->sincronizarPredecessorsYNotificar($tarea, $predecessors);
                });

                Notification::make()->success()->title(__('inspeccion.tarea.arbol.notificaciones.creada'))->send();
            });
    }

    /**
     * Portado de axon (ActivityAccordion::scheduleDatesFromPreviousAction()):
     * sugiere start_date a partir del due_date de la tarea anterior (mismo
     * orden, misma Actividad) — el usuario confirma/ajusta antes de guardar.
     */
    public function agendarFechasDesdeAnteriorAction(): Action
    {
        return Action::make('agendarFechasDesdeAnterior')
            ->label(__('inspeccion.tarea.arbol.agendar_desde_anterior'))
            ->icon(Heroicon::OutlinedCalendarDays)
            ->record(fn (array $arguments) => $this->resolveTarea($arguments))
            ->authorize('update')
            ->schema(function (array $arguments): array {
                $anterior = $this->tareaAnterior($this->resolveTarea($arguments));

                $info = $anterior
                    ? __('inspeccion.tarea.arbol.tarea_anterior_info', [
                        'nombre' => $anterior->nombre,
                        'fecha' => $anterior->due_date->format('d/m/Y'),
                    ])
                    : __('inspeccion.tarea.arbol.sin_tarea_anterior');

                return [
                    Section::make(__('inspeccion.tarea.arbol.agendar_desde_anterior'))
                        ->description($info)
                        ->icon(Heroicon::OutlinedCalendarDays)
                        ->columns(2)
                        ->schema([
                            DatePicker::make('start_date')->label(__('inspeccion.tarea.campos.start_date'))->required(),
                            DatePicker::make('due_date')->label(__('inspeccion.tarea.campos.due_date')),
                        ]),
                ];
            })
            ->fillForm(function (array $arguments): array {
                $tarea = $this->resolveTarea($arguments);
                $anterior = $this->tareaAnterior($tarea);
                $sugerido = $anterior?->due_date?->copy()->addDay();

                return [
                    'start_date' => ($sugerido ?? $tarea->start_date)?->format('Y-m-d'),
                    'due_date' => $tarea->due_date?->format('Y-m-d'),
                ];
            })
            ->action(function (array $arguments, array $data): void {
                $this->resolveTarea($arguments)->update([
                    'start_date' => $data['start_date'],
                    'due_date' => $data['due_date'] ?? null,
                ]);

                Notification::make()->success()->title(__('inspeccion.tarea.arbol.notificaciones.fechas_actualizadas'))->send();
            });
    }

    protected function tareaAnterior(Tarea $tarea): ?Tarea
    {
        return Tarea::query()
            ->where('actividad_id', $tarea->actividad_id)
            ->where('orden', '<', $tarea->orden)
            ->whereNotNull('due_date')
            ->orderByDesc('orden')
            ->first();
    }
}
