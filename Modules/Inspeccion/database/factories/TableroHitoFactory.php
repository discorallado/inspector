<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\GrupoHito;
use Modules\Inspeccion\Models\Tablero;
use Modules\Inspeccion\Models\TableroHito;

class TableroHitoFactory extends Factory
{
    protected $model = TableroHito::class;

    public function definition(): array
    {
        return [
            'tablero_id' => Tablero::factory(),
            'grupo_hito_id' => GrupoHito::factory(),
            'estado_avance_id' => EstadoAvance::factory(),
            'item' => fake()->numerify('#.#'),
            'nombre' => fake()->sentence(4),
            'peso' => fake()->randomFloat(2, 1, 20),
            'plan_inicio' => fake()->date(),
            'plan_fin' => fake()->date(),
        ];
    }
}
