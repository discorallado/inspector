<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrupoHito extends Model
{
    use HasFactory;

    protected $table = 'grupo_hitos';

    protected $fillable = [
        'organization_id',
        'nombre',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function tableroHitos(): HasMany
    {
        return $this->hasMany(TableroHito::class);
    }
}
