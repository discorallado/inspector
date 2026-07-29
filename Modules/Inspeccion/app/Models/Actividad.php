<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Actividad extends Model
{
    use HasFactory;

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
}
