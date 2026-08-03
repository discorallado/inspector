<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Modules\Inspeccion\Enums\ActividadEstado;
use Modules\Inspeccion\Enums\TaskStatus;
use Modules\Inspeccion\Services\CalculadorAvanceTablero;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;

class Actividad extends Model
{
    use HasFactory, HasFilamentComments, SoftDeletes;

    protected $table = 'actividades';

    protected $fillable = [
        'organization_id',
        'tablero_id',
        'nombre',
        'descripcion',
        'orden',
        'peso',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'orden' => 'integer',
            'peso' => 'decimal:2',
        ];
    }

    public function tablero(): BelongsTo
    {
        return $this->belongsTo(Tablero::class);
    }

    public function tareas(): HasMany
    {
        // id en vez de created_at como tiebreak: orden tiene default(0) sin
        // setear (nunca se expuso como campo editable), y una carga masiva
        // (seeders, import) puede compartir el mismo created_at hasta el
        // segundo — con ambos empatados, el orden final quedaba a merced
        // del orden físico de la BD, no garantizado.
        return $this->hasMany(Tarea::class)->orderBy('orden')->orderBy('id');
    }

    /**
     * Avance ponderado de esta Actividad (misma fórmula que
     * CalculadorAvanceTablero, aplicada solo a sus propias tareas). Se usa
     * como columna de referencia en ActividadesRelationManager, no se
     * persiste — a diferencia de Tablero.avance_global no hay un campo
     * `actividades.avance` que cachearlo. Reutiliza `tareas` si ya viene
     * eager-cargada (ver ActividadesRelationManager::table()) para no
     * disparar una query nueva por fila listada.
     */
    public function avance(): ?float
    {
        return CalculadorAvanceTablero::calcularSobreColeccion(
            $this->relationLoaded('tareas') ? $this->tareas : $this->tareas()->get()
        );
    }

    /**
     * Portado de axon (Activity::getStatusAttribute()): estado calculado a
     * partir del status de las Tareas, nunca persistido — a diferencia de
     * avance() (ponderado por peso) esto es un semáforo rápido sin pesos:
     * todas completadas -> Completada, alguna activa -> EnProgreso, resto
     * -> Pendiente.
     */
    public function estadoCalculado(): ActividadEstado
    {
        /** @var Collection<int, Tarea> $tareas */
        $tareas = $this->relationLoaded('tareas') ? $this->tareas : $this->tareas()->get();

        if ($tareas->isEmpty()) {
            return ActividadEstado::Pendiente;
        }

        if ($tareas->every(fn (Tarea $tarea) => $tarea->status === TaskStatus::Completada)) {
            return ActividadEstado::Completada;
        }

        $estadosActivos = [TaskStatus::EnProgreso, TaskStatus::EnRevision, TaskStatus::Bloqueada];

        if ($tareas->contains(fn (Tarea $tarea) => in_array($tarea->status, $estadosActivos, true))) {
            return ActividadEstado::EnProgreso;
        }

        return ActividadEstado::Pendiente;
    }
}
