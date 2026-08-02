<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChecklistItemLibrary extends Model
{
    use HasFactory;

    protected $table = 'checklist_item_libraries';

    protected $fillable = [
        'organization_id',
        'categoria',
        'item',
        'referencia_normativa',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function checklistTemplates(): BelongsToMany
    {
        return $this->belongsToMany(ChecklistTemplate::class, 'checklist_template_items')
            ->withPivot('orden');
    }
}
