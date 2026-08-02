<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoAvance extends Model
{
    use HasFactory;

    protected $table = 'estados_avance';

    protected $fillable = [
        'organization_id',
        'nombre',
        'codigo',
        'valor',
        'excluye_calculo',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'excluye_calculo' => 'boolean',
        ];
    }

    public function hitosLegados(): HasMany
    {
        return $this->hasMany(HitoLegado::class);
    }
}
