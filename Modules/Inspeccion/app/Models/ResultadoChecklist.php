<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultadoChecklist extends Model
{
    use HasFactory;

    protected $table = 'resultados_checklist';

    protected $fillable = [
        'organization_id',
        'nombre',
        'orden',
    ];

    public function checklistEjecucionItems(): HasMany
    {
        return $this->hasMany(ChecklistEjecucionItem::class);
    }
}
