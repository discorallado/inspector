<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inspeccion\Enums\TaskPriority;
use Modules\Inspeccion\Enums\TaskStatus;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;

class Tarea extends Model
{
    use HasFactory, HasFilamentComments, SoftDeletes;

    protected $table = 'tareas';

    protected $fillable = [
        'organization_id',
        'actividad_id',
        'parent_tarea_id',
        'tablero_hito_id',
        'code',
        'nombre',
        'descripcion',
        'status',
        'priority',
        'orden',
        'start_date',
        'due_date',
        'completed_at',
        'estimated_hours',
        'actual_hours',
        'peso',
        'excluye_calculo',
        'real_inicio',
        'real_fin',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'orden' => 'integer',
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'estimated_hours' => 'float',
            'actual_hours' => 'float',
            'peso' => 'decimal:2',
            'excluye_calculo' => 'boolean',
            'real_inicio' => 'date',
            'real_fin' => 'date',
        ];
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    /**
     * `code` ya no es un TextInput libre (pedido del usuario: la
     * numeración TP-1.1, 1.2, etc. se recalcula sola al crear/insertar/
     * reordenar) — vive acá como la única fuente de verdad del formato,
     * para que ActividadesRelationManager no la repita en cada acción que
     * la necesita.
     */
    public static function generarCode(string $tableroTag, int $actividadOrden, int $tareaOrden): string
    {
        return "{$tableroTag}-{$actividadOrden}.{$tareaOrden}";
    }

    /**
     * Portado de axon (Task::isOverdue()). Mismo criterio que
     * Observacion::estaVencida(): vencida solo si de verdad no se completó.
     */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && ! $this->status->isCompleted();
    }

    public function parentTarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, 'parent_tarea_id');
    }

    public function subtareas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'parent_tarea_id');
    }

    /**
     * Puente temporal con el sistema viejo (ver ADR 0012): clave de
     * matcheo estable para MigrarHitosATareasCommand, independiente del
     * contenido editable de HitoLegado. Se va con el cleanup de PR9. FK
     * explícita: la columna sigue llamándose tablero_hito_id (no se tocó
     * al renombrar el modelo, ver la migración de rename de tablas).
     */
    public function hitoLegado(): BelongsTo
    {
        return $this->belongsTo(HitoLegado::class, 'tablero_hito_id');
    }

    /**
     * PR8 (ADR 0015): a diferencia de axon (TaskLink.source_id/target_id
     * son ULID, sin colisión posible entre Task y Activity), acá
     * tarea_links.source_id/target_id son enteros autoincrementales —
     * una Tarea #5 y una Actividad #5 comparten id. Para no introducir esa
     * ambigüedad, el Gantt de Inspeccion solo permite dependencias
     * Tarea-Tarea (TableroGanttChart valida esto antes de crear el link,
     * y el JS bloquea el intento desde una fila de Actividad). Con esa
     * restricción, source_id/target_id son siempre ids de Tarea reales.
     */
    public function linksComoOrigen(): HasMany
    {
        return $this->hasMany(TareaLink::class, 'source_id');
    }

    public function linksComoDestino(): HasMany
    {
        return $this->hasMany(TareaLink::class, 'target_id');
    }

    /**
     * Portado de axon (Task::predecessors()/successors()): forma cómoda de
     * leer el mismo grafo que linksComoOrigen()/linksComoDestino() ya
     * exponen, para el multi-select de predecesoras en el modal de editar
     * (TareaDependencyService).
     */
    public function predecessors(): BelongsToMany
    {
        return $this->belongsToMany(Tarea::class, 'tarea_links', 'target_id', 'source_id')
            ->withPivot('id', 'type');
    }

    public function successors(): BelongsToMany
    {
        return $this->belongsToMany(Tarea::class, 'tarea_links', 'source_id', 'target_id')
            ->withPivot('id', 'type');
    }
}
