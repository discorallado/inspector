<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Tablero extends Model
{
    use HasFactory;

    protected $table = 'tableros';

    protected $fillable = [
        'organization_id',
        'proyecto_id',
        'tag',
        'nombre',
        'fabricante',
        'oc_contrato',
        'avance_global',
        'avance_calculado_at',
    ];

    protected function casts(): array
    {
        return [
            'avance_global' => 'decimal:2',
            'avance_calculado_at' => 'datetime',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /**
     * Sistema viejo (deprecado, ver HitoLegado) — árbol paralelo a
     * actividades(), no una capa por encima. No confundir jerarquías.
     */
    public function hitosLegados(): HasMany
    {
        return $this->hasMany(HitoLegado::class);
    }

    public function actividades(): HasMany
    {
        // orden tiebreak: por default(0) sin setear, empatan todas — id
        // como segundo criterio da un orden estable (insertion order) en
        // vez de depender del orden físico no garantizado de la BD.
        return $this->hasMany(Actividad::class)->orderBy('orden')->orderBy('id');
    }

    public function tareas(): HasManyThrough
    {
        return $this->hasManyThrough(Tarea::class, Actividad::class);
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(Observacion::class);
    }

    public function controlCambios(): HasMany
    {
        return $this->hasMany(ControlCambio::class);
    }

    public function visitasInspeccion(): BelongsToMany
    {
        return $this->belongsToMany(VisitaInspeccion::class, 'tablero_visita_inspeccion');
    }

    public function pruebas(): HasMany
    {
        return $this->hasMany(Prueba::class);
    }
}
