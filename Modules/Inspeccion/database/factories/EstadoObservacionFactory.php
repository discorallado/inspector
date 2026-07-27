<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\EstadoObservacion;

class EstadoObservacionFactory extends Factory
{
    protected $model = EstadoObservacion::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'codigo' => fake()->unique()->slug(2),
            'es_terminal' => false,
            'orden' => fake()->numberBetween(1, 10),
        ];
    }
}
