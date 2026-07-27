<?php

namespace Modules\Inspeccion\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inspeccion\Services\CalculadorEstadoVisita;

class VisitaInspeccion extends Model
{
    use HasFactory;

    protected $table = 'visitas_inspeccion';

    protected $fillable = [
        'organization_id',
        'proyecto_id',
        'inspector_id',
        'fecha',
        'observaciones_generales',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function tableros(): BelongsToMany
    {
        return $this->belongsToMany(Tablero::class, 'tablero_visita_inspeccion');
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(Observacion::class);
    }

    public function checklistEjecuciones(): HasMany
    {
        return $this->hasMany(ChecklistEjecucion::class);
    }

    public function estadoGeneral(): string
    {
        return app(CalculadorEstadoVisita::class)->calcular($this);
    }
}
