<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoObservacion extends Model
{
    use HasFactory;

    protected $table = 'estados_observacion';

    protected $fillable = [
        'organization_id',
        'nombre',
        'codigo',
        'es_terminal',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'es_terminal' => 'boolean',
        ];
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(Observacion::class);
    }
}
