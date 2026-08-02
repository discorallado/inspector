<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\EstadoAvance;
use Modules\Inspeccion\Models\GrupoHitoLegado;
use Modules\Inspeccion\Models\HitoLegado;
use Modules\Inspeccion\Models\Tablero;

class HitoLegadoFactory extends Factory
{
    protected $model = HitoLegado::class;

    public function definition(): array
    {
        return [
            'tablero_id' => Tablero::factory(),
            'grupo_hito_id' => GrupoHitoLegado::factory(),
            'estado_avance_id' => EstadoAvance::factory(),
            'item' => fake()->numerify('#.#'),
            'nombre' => fake()->sentence(4),
            'peso' => fake()->randomFloat(2, 1, 20),
            'plan_inicio' => fake()->date(),
            'plan_fin' => fake()->date(),
        ];
    }
}
