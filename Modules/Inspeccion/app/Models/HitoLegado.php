<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sistema viejo (deprecado desde ADR 0009/0011, pendiente de drop en
 * PR9). Antes "TableroHito" — renombrado para que quede claro que NO es
 * parte de la jerarquía Tablero -> Actividad -> Tarea portada de axon,
 * es un árbol paralelo que cuelga de Tablero por separado.
 */
class HitoLegado extends Model
{
    use HasFactory;

    protected $table = 'hitos_legados';

    protected $fillable = [
        'organization_id',
        'tablero_id',
        'grupo_hito_id',
        'estado_avance_id',
        'item',
        'nombre',
        'peso',
        'plan_inicio',
        'plan_fin',
        'real_inicio',
        'real_fin',
        'responsable',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'peso' => 'decimal:2',
            'plan_inicio' => 'date',
            'plan_fin' => 'date',
            'real_inicio' => 'date',
            'real_fin' => 'date',
        ];
    }

    public function tablero(): BelongsTo
    {
        return $this->belongsTo(Tablero::class);
    }

    public function grupoHitoLegado(): BelongsTo
    {
        return $this->belongsTo(GrupoHitoLegado::class, 'grupo_hito_id');
    }

    public function estadoAvance(): BelongsTo
    {
        return $this->belongsTo(EstadoAvance::class);
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(Observacion::class);
    }
}
