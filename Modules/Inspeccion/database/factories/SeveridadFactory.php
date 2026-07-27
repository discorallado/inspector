<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\Severidad;

class SeveridadFactory extends Factory
{
    protected $model = Severidad::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'codigo' => fake()->unique()->slug(2),
            'orden' => fake()->numberBetween(1, 10),
        ];
    }
}
