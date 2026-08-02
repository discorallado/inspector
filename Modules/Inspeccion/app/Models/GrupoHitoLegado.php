<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sistema viejo (deprecado desde ADR 0009/0011, pendiente de drop en
 * PR9). Antes "GrupoHito" — ver HitoLegado para el porqué del rename.
 */
class GrupoHitoLegado extends Model
{
    use HasFactory;

    protected $table = 'grupos_hitos_legados';

    protected $fillable = [
        'organization_id',
        'nombre',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function hitosLegados(): HasMany
    {
        return $this->hasMany(HitoLegado::class, 'grupo_hito_id');
    }
}
