<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Severidad extends Model
{
    use HasFactory;

    protected $table = 'severidades';

    protected $fillable = [
        'organization_id',
        'nombre',
        'codigo',
        'orden',
    ];

    public function observaciones(): HasMany
    {
        return $this->hasMany(Observacion::class);
    }
}
