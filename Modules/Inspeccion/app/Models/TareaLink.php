<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Espejo de TaskLink de axon. Sin relaciones Eloquent hacia Tarea/Actividad
 * todavía — ver el comentario en Tarea::subtareas() sobre la ambigüedad de
 * source_id/target_id, se resuelve en PR8 junto con el Gantt.
 */
class TareaLink extends Model
{
    use HasFactory;

    protected $table = 'tarea_links';

    protected $fillable = [
        'organization_id',
        'source_id',
        'target_id',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
        ];
    }
}
