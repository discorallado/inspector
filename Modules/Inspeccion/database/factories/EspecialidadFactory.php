<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\Especialidad;

class EspecialidadFactory extends Factory
{
    protected $model = Especialidad::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'orden' => fake()->numberBetween(1, 10),
            'activo' => true,
        ];
    }
}
