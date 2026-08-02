<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistTemplate extends Model
{
    use HasFactory;

    protected $table = 'checklist_templates';

    protected $fillable = [
        'organization_id',
        'nombre',
    ];

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(ChecklistItemLibrary::class, 'checklist_template_items')
            ->withPivot('orden')
            ->orderByPivot('orden');
    }

    public function checklistEjecuciones(): HasMany
    {
        return $this->hasMany(ChecklistEjecucion::class);
    }
}
