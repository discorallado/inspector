<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\ChecklistTemplate;

class ChecklistTemplateFactory extends Factory
{
    protected $model = ChecklistTemplate::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Checklist '.fake()->unique()->word(),
        ];
    }
}
