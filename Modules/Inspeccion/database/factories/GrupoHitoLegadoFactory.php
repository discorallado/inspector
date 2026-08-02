<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\GrupoHitoLegado;

class GrupoHitoLegadoFactory extends Factory
{
    protected $model = GrupoHitoLegado::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(3, true),
            'orden' => fake()->numberBetween(1, 10),
            'activo' => true,
        ];
    }
}
