<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PruebaItem extends Model
{
    use HasFactory;

    protected $table = 'prueba_items';

    protected $fillable = [
        'prueba_id',
        'prueba_item_library_id',
        'categoria',
        'item',
        'referencia_normativa',
        'orden',
        'resultado_checklist_id',
        'observacion',
    ];

    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class);
    }

    public function pruebaItemLibrary(): BelongsTo
    {
        return $this->belongsTo(PruebaItemLibrary::class);
    }

    public function resultadoChecklist(): BelongsTo
    {
        return $this->belongsTo(ResultadoChecklist::class);
    }
}
