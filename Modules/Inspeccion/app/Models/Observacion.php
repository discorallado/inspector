<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Observacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'observaciones';

    protected $fillable = [
        'organization_id',
        'visita_inspeccion_id',
        'tablero_id',
        'tablero_hito_id',
        'especialidad_id',
        'tipo_observacion_id',
        'severidad_id',
        'descripcion',
        'responsable',
        'fecha_compromiso',
        'estado_observacion_id',
        'fecha_cierre',
        'observacion_cierre',
    ];

    protected function casts(): array
    {
        return [
            'fecha_compromiso' => 'date',
            'fecha_cierre' => 'date',
        ];
    }

    public function visitaInspeccion(): BelongsTo
    {
        return $this->belongsTo(VisitaInspeccion::class);
    }

    public function tablero(): BelongsTo
    {
        return $this->belongsTo(Tablero::class);
    }

    public function tableroHito(): BelongsTo
    {
        return $this->belongsTo(TableroHito::class);
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function tipoObservacion(): BelongsTo
    {
        return $this->belongsTo(TipoObservacion::class);
    }

    public function severidad(): BelongsTo
    {
        return $this->belongsTo(Severidad::class);
    }

    public function estadoObservacion(): BelongsTo
    {
        return $this->belongsTo(EstadoObservacion::class);
    }

    protected function diasAbierta(): Attribute
    {
        return Attribute::get(fn () => $this->fecha_cierre
            ? (int) $this->created_at->diffInDays($this->fecha_cierre)
            : (int) $this->created_at->diffInDays(Carbon::now())
        );
    }

    public function estaVencida(): bool
    {
        return $this->fecha_compromiso !== null
            && $this->fecha_compromiso->isPast()
            && ! $this->estadoObservacion->es_terminal;
    }

    /**
     * Misma condición que estaVencida(), en forma de query, para reutilizar
     * en filtros de tabla sin duplicar la definición de "vencida".
     */
    public function scopeVencidas(Builder $query): Builder
    {
        return $query
            ->whereNotNull('fecha_compromiso')
            ->where('fecha_compromiso', '<', now())
            ->whereHas('estadoObservacion', fn (Builder $q) => $q->where('es_terminal', false));
    }
}
