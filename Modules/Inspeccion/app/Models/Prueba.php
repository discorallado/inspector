<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ChecklistEjecucion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'checklist_ejecuciones';

    protected $fillable = [
        'organization_id',
        'visita_inspeccion_id',
        'tablero_id',
        'checklist_template_id',
    ];

    public function visitaInspeccion(): BelongsTo
    {
        return $this->belongsTo(VisitaInspeccion::class);
    }

    public function tablero(): BelongsTo
    {
        return $this->belongsTo(Tablero::class);
    }

    public function checklistTemplate(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistEjecucionItem::class);
    }

    /**
     * Crea la ejecución y copia (snapshot) los ítems de la plantilla,
     * para que el histórico no cambie si el catálogo se edita después.
     */
    public static function crearDesdeTemplate(array $atributos, ChecklistTemplate $template): self
    {
        return DB::transaction(function () use ($atributos, $template) {
            $ejecucion = self::create([...$atributos, 'checklist_template_id' => $template->id]);

            foreach ($template->items as $item) {
                $ejecucion->items()->create([
                    'checklist_item_library_id' => $item->id,
                    'categoria' => $item->categoria,
                    'item' => $item->item,
                    'referencia_normativa' => $item->referencia_normativa,
                    'orden' => $item->pivot->orden,
                ]);
            }

            return $ejecucion;
        });
    }
}
