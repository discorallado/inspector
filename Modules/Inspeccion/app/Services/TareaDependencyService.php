<?php

namespace Modules\Inspeccion\Services;

use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Models\TareaLink;

/**
 * Portado de axon (app/Services/TaskDependencyService.php). Ahí el scope
 * de la detección de ciclos es project_id (columna real en task_links);
 * acá tarea_links no tiene columna de Tablero (ADR 0015 — mismo diseño
 * no-normalizado que axon, un link puede apuntar a una Actividad), así
 * que el scope se resuelve al vuelo: solo se consideran links entre
 * Tareas que pertenecen a Actividades del mismo Tablero que $tarea.
 */
class TareaDependencyService
{
    /**
     * Sincroniza las predecesoras de $tarea con la lista de ids recibida.
     * Los vínculos nuevos se crean como Fin→Inicio (type 0); los que ya
     * existían con otro tipo (definido desde el Gantt) se conservan
     * intactos. Devuelve los ids de predecesoras que se omitieron por
     * generar un ciclo.
     *
     * @param  list<int>  $predecessorIds
     * @return list<int>
     */
    public static function syncPredecessors(Tarea $tarea, array $predecessorIds): array
    {
        $predecessorIds = array_values(array_diff(array_unique($predecessorIds), [$tarea->id]));

        $current = TareaLink::query()
            ->where('target_id', $tarea->id)
            ->pluck('source_id')
            ->all();

        $toRemove = array_diff($current, $predecessorIds);
        $toAdd = array_diff($predecessorIds, $current);

        if ($toRemove !== []) {
            TareaLink::query()
                ->where('target_id', $tarea->id)
                ->whereIn('source_id', $toRemove)
                ->delete();
        }

        $skipped = [];
        $tableroId = $tarea->actividad->tablero_id;

        foreach ($toAdd as $predecessorId) {
            if (static::wouldCreateCycle($tableroId, $predecessorId, $tarea->id)) {
                $skipped[] = $predecessorId;

                continue;
            }

            TareaLink::query()->create([
                'source_id' => $predecessorId,
                'target_id' => $tarea->id,
                'type' => 0,
            ]);
        }

        return $skipped;
    }

    /**
     * ¿Agregar el vínculo $sourceId -> $targetId cerraría un ciclo? Cierto
     * si ya existe un camino de $targetId hacia $sourceId en el grafo
     * actual de tarea_links, recorriendo solo Tareas del mismo Tablero.
     */
    public static function wouldCreateCycle(int $tableroId, int $sourceId, int $targetId): bool
    {
        if ($sourceId === $targetId) {
            return true;
        }

        $tareaIds = Tarea::query()
            ->whereHas('actividad', fn ($query) => $query->where('tablero_id', $tableroId))
            ->pluck('id')
            ->all();

        $edgesByNode = TareaLink::query()
            ->whereIn('source_id', $tareaIds)
            ->whereIn('target_id', $tareaIds)
            ->get(['source_id', 'target_id'])
            ->groupBy('source_id');

        $visited = [];
        $queue = [$targetId];

        while ($queue !== []) {
            $node = array_shift($queue);

            if ($node === $sourceId) {
                return true;
            }

            if (isset($visited[$node])) {
                continue;
            }
            $visited[$node] = true;

            foreach ($edgesByNode->get($node, collect()) as $edge) {
                $queue[] = $edge->target_id;
            }
        }

        return false;
    }
}
