<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\PruebaTemplate;

class PruebaTemplateFactory extends Factory
{
    protected $model = PruebaTemplate::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Prueba '.fake()->unique()->word(),
        ];
    }
}
