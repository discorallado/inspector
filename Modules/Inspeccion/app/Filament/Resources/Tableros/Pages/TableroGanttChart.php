<?php

namespace Modules\Inspeccion\Filament\Resources\Tableros\Pages;

use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;
use Modules\Inspeccion\Filament\Resources\Tableros\TableroResource;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Models\TareaLink;

/**
 * Portado de axon (app/Filament/Resources/ProjectResource/Pages/GanttChart.php,
 * ver ADR 0009 §2.4 y ADR 0015), adaptado al scope de Tablero. Diferencias
 * de fondo respecto al patrón de axon:
 *
 * 1. Progreso de tarea: se reutiliza TaskStatus::valor() (ya existe, ver
 *    ese enum — es la misma fórmula que usa CalculadorAvanceTablero) en
 *    vez de duplicar un taskProgress() con su propio match() como hace
 *    axon.
 * 2. Dependencias (tarea_links): solo Tarea-Tarea, nunca involucrando una
 *    fila de Actividad — ver el comentario en Tarea::linksComoOrigen()
 *    para el porqué (colisión de ids entre Tarea/Actividad, tarea_links
 *    no tiene columna tablero_id para scopear directo como el project_id
 *    de axon). agregarLink()/eliminarLink() validan esto server-side; el
 *    JS del lado del cliente además bloquea el intento visualmente.
 */
class TableroGanttChart extends Page
{
    use InteractsWithRecord;

    protected static string $resource = TableroResource::class;

    protected string $view = 'inspeccion::filament.resources.tableros.pages.tablero-gantt-chart';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorize('view', $this->record);
    }

    public function getTitle(): string
    {
        return __('inspeccion.tarea.gantt.title');
    }

    private function diasTarea(Tarea $tarea): int
    {
        if ($tarea->start_date && $tarea->due_date) {
            return max(1, $tarea->start_date->diffInDays($tarea->due_date) + 1);
        }

        return 1;
    }

    /**
     * Estructura de datos para dhtmlxGantt: actividades como filas
     * resumen tipo "project" (no arrastrables, con % calculado), tareas
     * como filas hijo con barra y % individual, links desde tarea_links.
     */
    public function getGanttData(): array
    {
        $actividades = $this->record->actividades()
            ->with(['tareas' => fn ($q) => $q->orderBy('orden')])
            ->orderBy('orden')
            ->get();

        $rows = [];
        $tareaIds = [];

        foreach ($actividades as $actividad) {
            $tareas = $actividad->tareas;

            $totalDias = $tareas->sum(fn (Tarea $t) => $this->diasTarea($t));
            $progresoActividad = $totalDias > 0
                ? round($tareas->sum(fn (Tarea $t) => $this->diasTarea($t) * $t->status->valor()) / $totalDias, 2)
                : 0;

            $inicioActividad = $tareas->filter(fn (Tarea $t) => $t->start_date)
                ->sortBy(fn (Tarea $t) => $t->start_date->timestamp)
                ->first()?->start_date ?? ($actividad->start_date ?? now());

            $finActividad = $tareas->filter(fn (Tarea $t) => $t->due_date)
                ->sortByDesc(fn (Tarea $t) => $t->due_date->timestamp)
                ->first()?->due_date ?? ($actividad->end_date ?? now()->addDays(7));

            $rows[] = [
                'id' => 'act-'.$actividad->id,
                'text' => $actividad->nombre,
                'start_date' => $inicioActividad->format('Y-m-d'),
                'end_date' => $finActividad->format('Y-m-d'),
                'progress' => $progresoActividad,
                'open' => true,
                'type' => 'project',
                'readonly' => true,
            ];

            foreach ($tareas as $tarea) {
                $inicio = $tarea->start_date ?? now();
                $fin = $tarea->due_date ?? now()->addDays(3);

                $rows[] = [
                    'id' => $tarea->id,
                    'text' => $tarea->nombre,
                    'description' => $tarea->descripcion ?? '',
                    'start_date' => $inicio->format('Y-m-d'),
                    'end_date' => $fin->format('Y-m-d'),
                    'progress' => $tarea->status->valor(),
                    'parent' => 'act-'.$actividad->id,
                    'readonly' => false,
                ];

                $tareaIds[] = $tarea->id;
            }
        }

        $links = TareaLink::query()
            ->whereIn('source_id', $tareaIds)
            ->whereIn('target_id', $tareaIds)
            ->get()
            ->map(fn (TareaLink $link) => [
                'id' => $link->id,
                'source' => $link->source_id,
                'target' => $link->target_id,
                'type' => $link->type,
            ])
            ->values()
            ->all();

        return ['data' => $rows, 'links' => $links];
    }

    /**
     * @return Tarea la tarea, ya verificado que pertenece a este tablero.
     */
    private function tareaDelTablero(string $tareaId): Tarea
    {
        return Tarea::query()
            ->whereHas('actividad', fn ($q) => $q->where('tablero_id', $this->record->id))
            ->findOrFail($tareaId);
    }

    /**
     * /qa: ni el drag de la barra ni el modal validaban start <= end —
     * dhtmlx no manda fechas invertidas en uso normal, pero es un
     * wire:call público. Una tarea con due_date antes que start_date es
     * un dato sin sentido (barra de duración negativa en el propio
     * Gantt), no una variante de negocio válida.
     */
    private function validarRangoFechas(?string $startDate, ?string $endDate): void
    {
        if (! $startDate || ! $endDate) {
            return;
        }

        abort_if($endDate < $startDate, 422);
    }

    #[Renderless]
    public function updateTareaFechas(string $tareaId, string $startDate, string $endDate): void
    {
        $tarea = $this->tareaDelTablero($tareaId);

        $this->authorize('update', $tarea);

        $this->validarRangoFechas($startDate, $endDate);

        $tarea->update(['start_date' => $startDate, 'due_date' => $endDate]);
    }

    #[Renderless]
    public function persistirOrden(array $actividadIds, array $tareaOrdenes): void
    {
        $this->authorize('update', $this->record);

        // ids de Actividad reales de ESTE tablero — tareaOrdenes[]->actividadId
        // llega del cliente sin relación verificada con $actividadIds (son
        // dos arrays separados en el payload). Sin este chequeo, un
        // actividadId de OTRO tablero pasa igual el ->where('actividad_id', ...)
        // de abajo y reordena tareas ajenas — /revisor lo encontró
        // reproducido con un test.
        $actividadIdsDelTablero = $this->record->actividades()->pluck('id');

        DB::transaction(function () use ($actividadIds, $tareaOrdenes, $actividadIdsDelTablero) {
            foreach ($actividadIds as $index => $id) {
                Actividad::query()
                    ->where('id', $id)
                    ->where('tablero_id', $this->record->id)
                    ->update(['orden' => $index + 1]);
            }

            // $tareaOrdenes = [['actividadId' => ..., 'tareaIds' => [...]], ...]
            foreach ($tareaOrdenes as $grupo) {
                if (! $actividadIdsDelTablero->contains($grupo['actividadId'])) {
                    continue;
                }

                foreach ($grupo['tareaIds'] as $index => $tareaId) {
                    Tarea::query()
                        ->where('id', $tareaId)
                        ->where('actividad_id', $grupo['actividadId'])
                        ->update(['orden' => $index + 1]);
                }
            }
        });
    }

    #[Renderless]
    public function agregarLink(string $source, string $target, int $type): string
    {
        $this->authorize('update', $this->record);

        if (! str_starts_with($source, 'act-') && ! str_starts_with($target, 'act-')) {
            $origen = $this->tareaDelTablero($source);
            $destino = $this->tareaDelTablero($target);

            // 0=FS 1=SS 2=FF 3=SF (ver la migración de tarea_links) — el
            // cliente (dhtmlx) solo debería mandar estos 4, pero es un
            // wire:call público, no una validación de formulario.
            abort_unless(in_array($type, [0, 1, 2, 3], true), 422);

            abort_if($origen->id === $destino->id, 422);

            $link = TareaLink::create([
                'source_id' => $origen->id,
                'target_id' => $destino->id,
                'type' => $type,
            ]);

            return (string) $link->id;
        }

        // Intento de vincular una fila de Actividad (type=project): el JS
        // ya lo bloquea visualmente (onBeforeLinkAdd), esto es el
        // resguardo server-side — ver Tarea::linksComoOrigen(). 422 en vez
        // de una excepción de dominio nueva: es un caso de "input de
        // cliente que no debería llegar acá si el JS funciona", no una
        // regla de negocio con su propio vocabulario.
        abort(422, __('inspeccion.tarea.gantt.link_solo_tareas'));
    }

    #[Renderless]
    public function eliminarLink(string $linkId): void
    {
        $this->authorize('update', $this->record);

        $link = TareaLink::findOrFail($linkId);

        // whereIn contra las tareas del tablero, no un tablero_id directo
        // (la tabla no lo tiene) — mismo criterio de scoping que
        // getGanttData().
        $tareaIds = Tarea::query()
            ->whereHas('actividad', fn ($q) => $q->where('tablero_id', $this->record->id))
            ->pluck('id');

        abort_unless(
            $tareaIds->contains($link->source_id) && $tareaIds->contains($link->target_id),
            404
        );

        $link->delete();
    }

    #[Renderless]
    public function updateTareaDetalles(string $tareaId, string $nombre, string $descripcion, string $startDate, string $endDate): void
    {
        $tarea = $this->tareaDelTablero($tareaId);

        $this->authorize('update', $tarea);

        $this->validarRangoFechas($startDate ?: null, $endDate ?: null);

        $tarea->update([
            'nombre' => $nombre,
            'descripcion' => $descripcion ?: null,
            'start_date' => $startDate ?: null,
            'due_date' => $endDate ?: null,
        ]);
    }

    #[Renderless]
    public function refreshData(): void
    {
        $this->dispatch('gantt:refresh', $this->getGanttData());
    }
}
