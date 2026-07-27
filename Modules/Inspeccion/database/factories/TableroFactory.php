<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\Proyecto;
use Modules\Inspeccion\Models\Tablero;

class TableroFactory extends Factory
{
    protected $model = Tablero::class;

    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'tag' => strtoupper(fake()->unique()->lexify('T??')),
            'nombre' => 'Tablero '.fake()->word(),
            'fabricante' => fake()->company(),
            'oc_contrato' => fake()->bothify('OC-####'),
        ];
    }
}
