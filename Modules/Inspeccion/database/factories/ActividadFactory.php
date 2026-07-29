<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\Actividad;
use Modules\Inspeccion\Models\Tablero;

class ActividadFactory extends Factory
{
    protected $model = Actividad::class;

    public function definition(): array
    {
        return [
            'tablero_id' => Tablero::factory(),
            'nombre' => fake()->sentence(3),
            'descripcion' => fake()->optional()->paragraph(),
            'orden' => fake()->numberBetween(0, 10),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
        ];
    }
}
