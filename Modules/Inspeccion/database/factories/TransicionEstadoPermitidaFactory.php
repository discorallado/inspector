<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\TransicionEstadoPermitida;

class TransicionEstadoPermitidaFactory extends Factory
{
    protected $model = TransicionEstadoPermitida::class;

    public function definition(): array
    {
        return [
            'tipo_catalogo' => TransicionEstadoPermitida::TIPO_ESTADO_AVANCE,
            'estado_origen_id' => null,
            'estado_destino_id' => 1,
        ];
    }
}
