<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistEjecucionItem extends Model
{
    use HasFactory;

    protected $table = 'checklist_ejecucion_items';

    protected $fillable = [
        'checklist_ejecucion_id',
        'checklist_item_library_id',
        'categoria',
        'item',
        'referencia_normativa',
        'orden',
        'resultado_checklist_id',
        'observacion',
    ];

    public function checklistEjecucion(): BelongsTo
    {
        return $this->belongsTo(ChecklistEjecucion::class);
    }

    public function checklistItemLibrary(): BelongsTo
    {
        return $this->belongsTo(ChecklistItemLibrary::class);
    }

    public function resultadoChecklist(): BelongsTo
    {
        return $this->belongsTo(ResultadoChecklist::class);
    }
}
