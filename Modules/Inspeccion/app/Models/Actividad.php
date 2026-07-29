<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inspeccion\Services\CalculadorAvanceTablero;

class Actividad extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'actividades';

    protected $fillable = [
        'organization_id',
        'tablero_id',
        'nombre',
        'descripcion',
        'orden',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'orden' => 'integer',
        ];
    }

    public function tablero(): BelongsTo
    {
        return $this->belongsTo(Tablero::class);
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class)->orderBy('orden')->orderBy('created_at');
    }

    /**
     * Avance ponderado de esta Actividad (misma fórmula que
     * CalculadorAvanceTablero, aplicada solo a sus propias tareas). Se usa
     * como columna de referencia en ActividadesRelationManager, no se
     * persiste — a diferencia de Tablero.avance_global no hay un campo
     * `actividades.avance` que cachearlo.
     */
    public function avance(): ?float
    {
        return CalculadorAvanceTablero::calcularSobreColeccion($this->tareas()->get());
    }
}
