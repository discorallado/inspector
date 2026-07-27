<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoObservacion extends Model
{
    use HasFactory;

    protected $table = 'tipos_observacion';

    protected $fillable = [
        'organization_id',
        'nombre',
        'codigo',
        'requiere_severidad',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'requiere_severidad' => 'boolean',
        ];
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(Observacion::class);
    }
}
