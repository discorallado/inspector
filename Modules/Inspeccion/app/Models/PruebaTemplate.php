<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PruebaTemplate extends Model
{
    use HasFactory;

    protected $table = 'prueba_templates';

    protected $fillable = [
        'organization_id',
        'nombre',
    ];

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(PruebaItemLibrary::class, 'prueba_template_items')
            ->withPivot('orden')
            ->orderByPivot('orden');
    }

    public function pruebas(): HasMany
    {
        return $this->hasMany(Prueba::class);
    }
}
