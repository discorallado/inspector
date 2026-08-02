<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PruebaItemLibrary extends Model
{
    use HasFactory;

    protected $table = 'prueba_item_libraries';

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

    public function pruebaTemplates(): BelongsToMany
    {
        return $this->belongsToMany(PruebaTemplate::class, 'prueba_template_items')
            ->withPivot('orden');
    }
}
