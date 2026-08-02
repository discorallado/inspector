<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\PruebaItemLibrary;

class PruebaItemLibraryFactory extends Factory
{
    protected $model = PruebaItemLibrary::class;

    public function definition(): array
    {
        return [
            'categoria' => fake()->randomElement(['Estructura', 'Cableado', 'Protecciones', 'Documentación']),
            'item' => fake()->sentence(6),
            'referencia_normativa' => 'IEC 61439-'.fake()->numberBetween(1, 6),
            'orden' => fake()->numberBetween(1, 50),
            'activo' => true,
        ];
    }
}
