<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\EstadoAvance;

class EstadoAvanceFactory extends Factory
{
    protected $model = EstadoAvance::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'codigo' => fake()->unique()->slug(2),
            'valor' => fake()->randomElement([0, 0.5, 1]),
            'excluye_calculo' => false,
            'orden' => fake()->numberBetween(1, 10),
        ];
    }
}
