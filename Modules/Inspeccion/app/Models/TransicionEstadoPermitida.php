<?php

namespace Modules\Inspeccion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransicionEstadoPermitida extends Model
{
    use HasFactory;

    protected $table = 'transiciones_estado_permitidas';

    public const TIPO_ESTADO_AVANCE = 'estado_avance';

    public const TIPO_ESTADO_OBSERVACION = 'estado_observacion';

    public const TIPO_ESTADO_CAMBIO = 'estado_cambio';

    public const TIPO_TAREA_STATUS = 'tarea_status';

    protected $fillable = [
        'organization_id',
        'tipo_catalogo',
        'estado_origen_id',
        'estado_destino_id',
        'estado_origen_codigo',
        'estado_destino_codigo',
    ];
}
