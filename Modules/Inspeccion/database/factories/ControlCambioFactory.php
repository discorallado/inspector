<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\ControlCambio;
use Modules\Inspeccion\Models\EstadoCambio;
use Modules\Inspeccion\Models\Tablero;

class ControlCambioFactory extends Factory
{
    protected $model = ControlCambio::class;

    public function definition(): array
    {
        return [
            'tablero_id' => Tablero::factory(),
            'estado_cambio_id' => EstadoCambio::factory(),
            'descripcion' => fake()->paragraph(),
            'responsable' => fake()->name(),
            'fecha' => fake()->date(),
        ];
    }
}
