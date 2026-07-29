<?php

namespace Modules\Inspeccion\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inspeccion\Models\Tarea;
use Modules\Inspeccion\Models\TareaLink;

class TareaLinkFactory extends Factory
{
    protected $model = TareaLink::class;

    public function definition(): array
    {
        return [
            'source_id' => Tarea::factory(),
            'target_id' => Tarea::factory(),
            'type' => 0,
        ];
    }
}
