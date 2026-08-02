<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Antes "ChecklistEjecucion" (ver ADR de rename). El punto de entrada
 * ahora es Tablero (PruebasRelationManager), no VisitaInspeccion — por
 * eso visita_inspeccion_id es nullable.
 */
class Prueba extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pruebas';

    protected $fillable = [
        'organization_id',
        'visita_inspeccion_id',
        'tablero_id',
        'prueba_template_id',
    ];

    public function visitaInspeccion(): BelongsTo
    {
        return $this->belongsTo(VisitaInspeccion::class);
    }

    public function tablero(): BelongsTo
    {
        return $this->belongsTo(Tablero::class);
    }

    public function pruebaTemplate(): BelongsTo
    {
        return $this->belongsTo(PruebaTemplate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PruebaItem::class);
    }

    /**
     * Crea la prueba y copia (snapshot) los ítems de la plantilla, para
     * que el histórico no cambie si el catálogo se edita después.
     */
    public static function crearDesdeTemplate(array $atributos, PruebaTemplate $template): self
    {
        return DB::transaction(function () use ($atributos, $template) {
            $prueba = self::create([...$atributos, 'prueba_template_id' => $template->id]);

            foreach ($template->items as $item) {
                $prueba->items()->create([
                    'prueba_item_library_id' => $item->id,
                    'categoria' => $item->categoria,
                    'item' => $item->item,
                    'referencia_normativa' => $item->referencia_normativa,
                    'orden' => $item->pivot->orden,
                ]);
            }

            return $prueba;
        });
    }
}
