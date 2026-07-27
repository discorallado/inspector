<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlCambio extends Model
{
    use HasFactory;

    protected $table = 'control_cambios';

    protected $fillable = [
        'organization_id',
        'tablero_id',
        'estado_cambio_id',
        'descripcion',
        'responsable',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function tablero(): BelongsTo
    {
        return $this->belongsTo(Tablero::class);
    }

    public function estadoCambio(): BelongsTo
    {
        return $this->belongsTo(EstadoCambio::class);
    }
}
