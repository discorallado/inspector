<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\ChecklistEjecucion;
use Modules\Inspeccion\Models\ChecklistEjecucionItem;

class ChecklistEjecucionItemFactory extends Factory
{
    protected $model = ChecklistEjecucionItem::class;

    public function definition(): array
    {
        return [
            'checklist_ejecucion_id' => ChecklistEjecucion::factory(),
            'categoria' => fake()->word(),
            'item' => fake()->sentence(6),
            'referencia_normativa' => 'IEC 61439-'.fake()->numberBetween(1, 6),
            'orden' => fake()->numberBetween(1, 50),
        ];
    }
}
