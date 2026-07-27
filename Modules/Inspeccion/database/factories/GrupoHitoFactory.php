<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\GrupoHito;

class GrupoHitoFactory extends Factory
{
    protected $model = GrupoHito::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(3, true),
            'orden' => fake()->numberBetween(1, 10),
            'activo' => true,
        ];
    }
}
