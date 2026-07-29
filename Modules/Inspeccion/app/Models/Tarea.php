<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inspeccion\Enums\TaskPriority;
use Modules\Inspeccion\Enums\TaskStatus;

class Tarea extends Model
{
    use HasFactory;

    protected $table = 'tareas';

    protected $fillable = [
        'organization_id',
        'actividad_id',
        'parent_tarea_id',
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
            'real_inicio' => 'date',
            'real_fin' => 'date',
        ];
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function parentTarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, 'parent_tarea_id');
    }

    public function subtareas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'parent_tarea_id');
    }

    // predecesoras()/sucesoras() (relaciones vía tarea_links) se agregan
    // en PR8 junto con el Gantt: source_id/target_id de tarea_links puede
    // apuntar a una Tarea O a una Actividad (igual que axon, ver el
    // comentario de la migración), y con id autoincremental (no ULID como
    // axon) hay colisión real de ids entre ambas tablas — un
    // BelongsToMany ingenuo acá sería sutilmente incorrecto sin resolver
    // primero cómo se distingue el tipo del extremo del link.
}
