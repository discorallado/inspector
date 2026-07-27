<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\ResultadoChecklist;

class ResultadoChecklistFactory extends Factory
{
    protected $model = ResultadoChecklist::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'orden' => fake()->numberBetween(1, 10),
        ];
    }
}
