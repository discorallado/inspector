<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stub mínimo mientras este repo es standalone.
 *
 * TODO: eliminar este modelo al integrar a axon y re-apuntar
 * Tablero::proyecto() al Proyecto real del PMIS.
 */
class Proyecto extends Model
{
    use HasFactory;

    protected $table = 'proyectos';

    protected $fillable = [
        'organization_id',
        'nombre',
    ];

    public function tableros(): HasMany
    {
        return $this->hasMany(Tablero::class);
    }

    public function visitasInspeccion(): HasMany
    {
        return $this->hasMany(VisitaInspeccion::class);
    }
}
