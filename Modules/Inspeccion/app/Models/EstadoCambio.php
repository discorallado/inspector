<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoCambio extends Model
{
    use HasFactory;

    protected $table = 'estados_cambio';

    protected $fillable = [
        'organization_id',
        'nombre',
        'codigo',
        'orden',
    ];

    public function controlCambios(): HasMany
    {
        return $this->hasMany(ControlCambio::class);
    }
}
