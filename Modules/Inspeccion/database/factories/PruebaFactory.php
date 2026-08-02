<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\Prueba;
use Modules\Inspeccion\Models\PruebaTemplate;
use Modules\Inspeccion\Models\Tablero;

class PruebaFactory extends Factory
{
    protected $model = Prueba::class;

    /**
     * visita_inspeccion_id nulo por default: el punto de entrada real es
     * Tablero (PruebasRelationManager), la visita es opcional.
     */
    public function definition(): array
    {
        return [
            'visita_inspeccion_id' => null,
            'tablero_id' => Tablero::factory(),
            'prueba_template_id' => PruebaTemplate::factory(),
        ];
    }
}
