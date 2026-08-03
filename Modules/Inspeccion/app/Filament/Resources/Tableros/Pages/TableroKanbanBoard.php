<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Modules\Inspeccion\Enums\TaskPriority;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Exceptions\TransicionEstadoInvalidaException;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;
use Modules\Inspeccion\Models\Tarea;

/**
 * Portado de axon (app/Filament/Resources/ProjectResource/Pages/KanbanBoard.php,
 * ver ADR 0009 §2.4), adaptado al scope de Tablero: acá Tablero ya es el
 * nivel que scopea las tareas (vía actividad->tablero_id), no hace falta el
 * filtro de proyecto suelto que tiene axon.
 *
 * Diferencia deliberada con axon: acá updateTareaStatus() NO es
 * #[Renderless] — un salto de estado inválido (TransicionEstadoGuard,
 * TareaObserver::saving) lanza una excepción, y sin re-render Livewire la
 * tarjeta quedaría visualmente en la columna nueva (el drag ya la movió en
 * el DOM vía SortableJS) aunque el update se haya descartado. Con render
 * normal, Livewire recalcula getColumns() desde la BD real y
 * Livewire.hook('morph.updated') reinicializa Sortable — la tarjeta vuelve
 * a su columna real. axon no necesita esto (TaskStatus ahí no valida
 * transiciones).
 */
class TableroKanbanBoard extends Page
{
    use InteractsWithRecord;

    protected static string $resource = TableroResource::class;

    protected string $view = 'inspeccion::filament.resources.tableros.pages.tablero-kanban-board';

    public ?int $filterActividad = null;

    public ?string $filterPriority = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        $this->authorize('view', $this->record);
    }

    public function getTitle(): string
    {
        return __('inspeccion.tarea.kanban.title');
    }

    /**
     * @return array<int, array{status: TaskStatus, tareas: Collection<int, Tarea>}>
     */
    public function getColumns(): array
    {
        $query = Tarea::query()
            ->whereHas('actividad', fn ($q) => $q->where('tablero_id', $this->record->id))
            ->with('actividad')
            ->withCount('filamentComments')
            ->orderBy('orden')
            ->orderBy('id');

        if ($this->filterActividad) {
            $query->where('actividad_id', $this->filterActividad);
        }

        if ($this->filterPriority) {
            $query->where('priority', $this->filterPriority);
        }

        $agrupadas = $query->get()->groupBy(fn (Tarea $tarea) => $tarea->status->value);

        return collect(TaskStatus::cases())->map(fn (TaskStatus $status) => [
            'status' => $status,
            'tareas' => $agrupadas->get($status->value, collect()),
        ])->all();
    }

    /**
     * @return array<int, string>
     */
    public function getActividadesParaFiltro(): array
    {
        return $this->record->actividades()
            ->orderBy('orden')
            ->pluck('nombre', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function getPrioridadesParaFiltro(): array
    {
        return collect(TaskPriority::cases())
            ->mapWithKeys(fn (TaskPriority $p) => [$p->value => $p->getLabel()])
            ->all();
    }

    public function urlDetalleTarea(Tarea $tarea): string
    {
        return TableroResource::getUrl('actividad-detalle', [
            'record' => $this->record,
            'actividadId' => $tarea->actividad_id,
            'focus' => $tarea->id,
        ]);
    }

    public function updateTareaStatus(string $tareaId, string $status): void
    {
        // Sin este scope, cualquier tareaId válido de CUALQUIER tablero
        // pasa este findOrFail — TareaPolicy::update() es un Gate por rol,
        // no por registro, así que el authorize() de abajo no detecta que
        // la tarea no pertenece a $this->record. /revisor lo encontró: se
        // podía mover una tarea de otro tablero operando desde este board.
        $tarea = Tarea::query()
            ->whereHas('actividad', fn ($q) => $q->where('tablero_id', $this->record->id))
            ->findOrFail($tareaId);

        $this->authorize('update', $tarea);

        $nuevoStatus = TaskStatus::tryFrom($status);

        if (! $nuevoStatus) {
            abort(422);
        }

        try {
            $tarea->update(['status' => $nuevoStatus]);
        } catch (TransicionEstadoInvalidaException $e) {
            Notification::make()
                ->danger()
                ->title($e->getMessage())
                ->send();
        }
    }
}
