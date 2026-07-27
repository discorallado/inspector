<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\TipoObservacion;

class TipoObservacionFactory extends Factory
{
    protected $model = TipoObservacion::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'codigo' => fake()->unique()->slug(2),
            'requiere_severidad' => false,
            'orden' => fake()->numberBetween(1, 10),
        ];
    }
}
