<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\Prueba;
use Modules\Inspeccion\Models\PruebaItem;

class PruebaItemFactory extends Factory
{
    protected $model = PruebaItem::class;

    public function definition(): array
    {
        return [
            'prueba_id' => Prueba::factory(),
            'categoria' => fake()->word(),
            'item' => fake()->sentence(6),
            'referencia_normativa' => 'IEC 61439-'.fake()->numberBetween(1, 6),
            'orden' => fake()->numberBetween(1, 50),
        ];
    }
}
